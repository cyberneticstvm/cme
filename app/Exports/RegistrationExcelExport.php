<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Throwable;
use XMLWriter;
use ZipArchive;

class RegistrationExcelExport
{
    /** @param array<string, mixed> $filters */
    public function create(Builder $query, array $filters = []): string
    {
        $sheetPath = tempnam(storage_path('app'), 'delegate-sheet-');
        $path = tempnam(storage_path('app'), 'delegate-export-');
        if ($sheetPath === false || $path === false) {
            throw new RuntimeException('Unable to create the Excel export.');
        }

        try {
            $xml = new XMLWriter;
            $xml->openUri($sheetPath);
            $xml->startDocument('1.0', 'UTF-8', 'yes');
            $xml->startElement('worksheet');
            $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $xml->startElement('sheetViews');
            $xml->startElement('sheetView');
            $xml->writeAttribute('workbookViewId', '0');
            $xml->startElement('pane');
            foreach (['ySplit' => '4', 'topLeftCell' => 'A5', 'activePane' => 'bottomLeft', 'state' => 'frozen'] as $key => $value) {
                $xml->writeAttribute($key, $value);
            }
            $xml->endElement();
            $xml->endElement();
            $xml->endElement();
            $xml->startElement('cols');
            foreach ([34, 27, 19, 34, 22, 32, 22, 22, 17, 25, 25, 25, 25, 55, 55, 19, 18, 30, 25, 24, 25, 34, 19, 25, 25] as $index => $width) {
                $xml->startElement('col');
                foreach (['min' => (string) ($index + 1), 'max' => (string) ($index + 1), 'width' => (string) $width, 'customWidth' => '1'] as $key => $value) {
                    $xml->writeAttribute($key, $value);
                }
                $xml->endElement();
            }
            $xml->endElement();
            $xml->startElement('sheetData');
            $this->row($xml, 1, ['VISION X 2026 | Delegate registrations'], 1);
            $summary = $filters === [] ? 'All registrations' : collect($filters)->map(fn ($value, $key) => ucfirst($key).': '.$value)->implode(' | ');
            $this->row($xml, 2, ['Exported '.now()->timezone('Asia/Kolkata')->format('d M Y, h:i A').' IST | '.$summary], 3);
            $this->row($xml, 4, ['Registration ID', 'Full name', 'Profession', 'Email address', 'Mobile number', 'Organisation', 'City / town', 'State / region', 'Status', 'Registered at (IST)', 'Consent recorded (IST)', 'Acknowledgement sent (IST)', 'Organiser notified (IST)', 'Delegate message', 'Internal notes', 'Payment status', 'Amount (INR)', 'Payment reference', 'Payment confirmed (IST)', 'Confirmed by', 'Pass emailed (IST)', 'Pass emailed to', 'Attendance', 'Checked in at (IST)', 'Checked in by'], 2);
            $number = 5;
            foreach ($query->reorder()->lazyByIdDesc(500) as $registration) {
                $date = fn ($value) => $value?->timezone('Asia/Kolkata')->format('Y-m-d H:i:s') ?? '';
                $this->row($xml, $number++, [
                    $registration->reference, $registration->full_name, $registration->profession->label(),
                    $registration->email, $registration->phone, $registration->organisation ?? '',
                    $registration->city, $registration->state, $registration->status->label(),
                    $date($registration->created_at), $date($registration->consented_at),
                    $date($registration->acknowledgement_sent_at), $date($registration->notification_sent_at),
                    $registration->message ?? '', $registration->admin_notes ?? '',
                    $registration->payment_status->label(), $registration->payment_amount ?? '',
                    $registration->payment_reference ?? '', $date($registration->payment_confirmed_at),
                    $registration->payment_confirmed_by ?? '', $date($registration->pass_sent_at), $registration->pass_sent_to ?? '',
                    $registration->checked_in_at ? 'Checked in' : 'Not checked in', $date($registration->checked_in_at), $registration->checked_in_by ?? '',
                ]);
            }
            $xml->endElement();
            $xml->startElement('autoFilter');
            $xml->writeAttribute('ref', 'A4:Y'.max(4, $number - 1));
            $xml->endElement();
            $xml->startElement('mergeCells');
            foreach (['A1:Y1', 'A2:Y2'] as $reference) {
                $xml->startElement('mergeCell');
                $xml->writeAttribute('ref', $reference);
                $xml->endElement();
            }
            $xml->endElement();
            $xml->endElement();
            $xml->endDocument();
            $xml->flush();
            unset($xml);

            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to package the Excel export.');
            }
            foreach ($this->parts() as $name => $contents) {
                if (! $zip->addFromString($name, $contents)) {
                    throw new RuntimeException('Unable to write the Excel workbook.');
                }
            }
            $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
            if (! $zip->close()) {
                throw new RuntimeException('Unable to complete the Excel export.');
            }

            return $path;
        } catch (Throwable $exception) {
            @unlink($path);
            throw $exception;
        } finally {
            @unlink($sheetPath);
        }
    }

    /** @param array<int, string> $values */
    private function row(XMLWriter $xml, int $number, array $values, int $style = 0): void
    {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) $number);
        if ($number === 1 || $number === 4) {
            $xml->writeAttribute('ht', $number === 1 ? '34' : '29');
            $xml->writeAttribute('customHeight', '1');
        }
        foreach ($values as $index => $value) {
            $xml->startElement('c');
            $xml->writeAttribute('r', chr(65 + $index).$number);
            $xml->writeAttribute('t', 'inlineStr');
            $xml->writeAttribute('s', (string) $style);
            $xml->startElement('is');
            $xml->startElement('t');
            $xml->writeAttribute('xml:space', 'preserve');
            $xml->text(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '');
            $xml->endElement();
            $xml->endElement();
            $xml->endElement();
        }
        $xml->endElement();
    }

    /** @return array<string, string> */
    private function parts(): array
    {
        return [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Registrations" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="11"/><color rgb="FF284C6B"/><name val="Calibri"/></font><font><b/><sz val="20"/><color rgb="FF092D62"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF668298"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF092D62"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
        ];
    }
}
