<?php

namespace Tests\Feature;

use App\Auth\EnvironmentAdminProvider;
use App\Models\Registration;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class RegistrationExcelExportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_export_is_a_real_xlsx_and_contains_all_matching_rows_beyond_the_current_page(): void
    {
        Registration::factory()->count(17)->create(['profession' => 'doctor', 'status' => 'reviewed']);
        Registration::factory()->create(['full_name' => 'Excluded Optician', 'profession' => 'optician']);
        $this->signIn();

        $response = $this->get(route('admin.registrations.export', ['profession' => 'doctor', 'status' => 'reviewed']));

        $response->assertOk()->assertDownload()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $this->assertNotFalse($zip->getFromName('[Content_Types].xml'));
            $this->assertNotFalse($zip->getFromName('xl/styles.xml'));
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $document = new DOMDocument;
            $this->assertTrue($document->loadXML($xml));
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $this->assertSame(17, $xpath->query('//s:row[number(@r) >= 5]')->length);
            $this->assertSame('A4:Y21', $xpath->evaluate('string(//s:autoFilter/@ref)'));
            $this->assertSame('A5', $xpath->evaluate('string(//s:pane/@topLeftCell)'));
            $this->assertStringNotContainsString('Excluded Optician', $xml);
            $zip->close();
        } finally {
            unset($zip);
            unlink($path);
        }
    }

    public function test_export_preserves_phone_numbers_and_never_evaluates_submitted_text_as_formulas(): void
    {
        Registration::factory()->create(['full_name' => '=HYPERLINK("https://example.test")', 'profession' => 'optometrist', 'phone' => '+919497005735', 'organisation' => 'A & B <Clinic>', 'message' => '+SUM(1,2)', 'admin_notes' => '@unsafe-formula']);
        $this->signIn();

        $response = $this->get(route('admin.registrations.export'));

        $response->assertOk()->assertDownload();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $zip->open($path);
            $document = new DOMDocument;
            $this->assertTrue($document->loadXML($zip->getFromName('xl/worksheets/sheet1.xml')));
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $this->assertSame('=HYPERLINK("https://example.test")', $xpath->evaluate('string(//s:c[@r="B5"]/s:is/s:t)'));
            $this->assertSame('+919497005735', $xpath->evaluate('string(//s:c[@r="E5"]/s:is/s:t)'));
            $this->assertSame('A & B <Clinic>', $xpath->evaluate('string(//s:c[@r="F5"]/s:is/s:t)'));
            $this->assertSame('@unsafe-formula', $xpath->evaluate('string(//s:c[@r="O5"]/s:is/s:t)'));
            $this->assertSame(0, $xpath->query('//s:f')->length);
            $this->assertSame(25, $xpath->query('//s:row[@r="5"]/s:c[@t="inlineStr"]')->length);
            $zip->close();
        } finally {
            unset($zip);
            unlink($path);
        }
    }

    public function test_export_with_no_matches_is_a_valid_empty_workbook_with_headers(): void
    {
        $this->signIn();

        $response = $this->get(route('admin.registrations.export', ['q' => 'no-matches']));

        $response->assertOk()->assertDownload();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $zip->open($path);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertStringContainsString('Full name', $xml);
            $this->assertStringContainsString('A4:Y4', $xml);
            $zip->close();
        } finally {
            unset($zip);
            unlink($path);
        }
    }

    public function test_payment_filter_and_payment_details_are_included_in_excel(): void
    {
        Registration::factory()->create(['full_name' => 'Paid Delegate', 'payment_status' => 'confirmed', 'payment_amount' => '1250.50', 'payment_reference' => 'PAYMENT-TEST', 'payment_confirmed_at' => now(), 'payment_confirmed_by' => 'event-admin', 'pass_sent_at' => now(), 'pass_sent_to' => 'delegate@example.test']);
        Registration::factory()->create(['full_name' => 'Unpaid Delegate']);
        $this->signIn();
        $response = $this->get(route('admin.registrations.export', ['payment_status' => 'confirmed']));
        $response->assertOk()->assertDownload();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $zip->open($path);
            $document = new DOMDocument;
            $this->assertTrue($document->loadXML($zip->getFromName('xl/worksheets/sheet1.xml')));
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $this->assertSame(1, $xpath->query('//s:row[number(@r) >= 5]')->length);
            $this->assertSame('Confirmed', $xpath->evaluate('string(//s:c[@r="P5"]/s:is/s:t)'));
            $this->assertSame('1250.50', $xpath->evaluate('string(//s:c[@r="Q5"]/s:is/s:t)'));
            $this->assertSame('PAYMENT-TEST', $xpath->evaluate('string(//s:c[@r="R5"]/s:is/s:t)'));
            $this->assertSame('delegate@example.test', $xpath->evaluate('string(//s:c[@r="V5"]/s:is/s:t)'));
            $zip->close();
        } finally {
            unset($zip);
            unlink($path);
        }
    }

    private function signIn(): void
    {
        config(['admin.username' => 'event-admin', 'admin.password' => 'Simple-Test#42']);
        $provider = app(EnvironmentAdminProvider::class);
        $this->actingAs($provider->retrieveById($provider->identifier()), 'admin')->withSession(['admin_last_activity' => now()->timestamp]);
    }
}
