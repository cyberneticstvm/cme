<?php

namespace App\Models;

use App\PaymentStatus;
use App\Profession;
use App\RegistrationStatus;
use App\Services\RegistrationReferenceGenerator;
use Carbon\CarbonImmutable;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['full_name', 'profession', 'email', 'phone', 'organisation', 'city', 'state', 'message'])]
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Registration $registration): void {
            $registration->reference = app(RegistrationReferenceGenerator::class)->next();
        });
    }

    /** @param array<string, mixed> $filters */
    #[Scope]
    protected function filtered(Builder $query, array $filters): void
    {
        if (isset($filters['q']) && $filters['q'] !== '') {
            $search = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($filters['q'])).'%';
            $query->where(function (Builder $query) use ($search): void {
                foreach (['full_name', 'email', 'phone', 'organisation', 'city', 'reference'] as $column) {
                    $query->orWhereRaw("LOWER({$column}) LIKE ? ESCAPE '!'", [$search]);
                }
            });
        }
        if (! empty($filters['profession'])) {
            $query->where('profession', $filters['profession']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (($filters['attendance'] ?? '') === 'checked_in') {
            $query->whereNotNull('checked_in_at');
        } elseif (($filters['attendance'] ?? '') === 'not_checked_in') {
            $query->whereNull('checked_in_at');
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', CarbonImmutable::parse($filters['from'], 'Asia/Kolkata')->startOfDay()->timezone(config('app.timezone')));
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<', CarbonImmutable::parse($filters['to'], 'Asia/Kolkata')->addDay()->startOfDay()->timezone(config('app.timezone')));
        }
    }

    public function canIssuePass(): bool
    {
        return $this->payment_status === PaymentStatus::Confirmed
            && $this->payment_confirmed_at !== null
            && $this->status !== RegistrationStatus::Cancelled;
    }

    public function canIssueCertificate(): bool
    {
        return $this->checked_in_at !== null && $this->status !== RegistrationStatus::Cancelled;
    }

    public function certificatePath(): ?string
    {
        return $this->certificate_id ? $this->certificate_id.'.pdf' : null;
    }

    public function passPath(): ?string
    {
        return $this->pass_id ? $this->pass_id.'.pdf' : null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'profession' => Profession::class,
            'status' => RegistrationStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_amount' => 'decimal:2',
            'payment_confirmed_at' => 'immutable_datetime',
            'payment_updated_at' => 'immutable_datetime',
            'pass_issued_at' => 'immutable_datetime',
            'pass_qr_version' => 'integer',
            'checked_in_at' => 'immutable_datetime',
            'certificate_issued_at' => 'immutable_datetime',
            'certificate_sent_at' => 'immutable_datetime',
            'pass_sent_at' => 'immutable_datetime',
            'consented_at' => 'immutable_datetime',
            'acknowledgement_sent_at' => 'immutable_datetime',
            'notification_sent_at' => 'immutable_datetime',
        ];
    }
}
