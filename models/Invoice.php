<?php

namespace TheWebsiteGuy\NexusCRM\Models;

use Winter\Storm\Database\Model;

/**
 * Invoice Model
 *
 * Two separate status fields:
 *  - internal_status : admin workflow stage (draft, sent)
 *  - status          : client-facing payment status (outstanding, due, overdue, cancelled, paid)
 */
class Invoice extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    // ── Internal (admin) status constants ─────────────────────
    const INTERNAL_DRAFT = 'draft';
    const INTERNAL_SENT  = 'sent';

    // ── Client-facing payment status constants ────────────────
    const STATUS_OUTSTANDING = 'outstanding';
    const STATUS_DUE         = 'due';
    const STATUS_OVERDUE     = 'overdue';
    const STATUS_CANCELLED   = 'cancelled';
    const STATUS_PAID        = 'paid';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_nexuscrm_invoices';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'amount' => 'float',
    ];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'issue_date',
        'due_date',
        'paid_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [
        'items' => [\TheWebsiteGuy\NexusCRM\Models\InvoiceItem::class],
    ];
    public $hasOneThrough = [];
    public $hasManyThrough = [];
    public $belongsTo = [
        'client' => [\TheWebsiteGuy\NexusCRM\Models\Client::class],
        'project' => [\TheWebsiteGuy\NexusCRM\Models\Project::class],
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Generate the next invoice number based on Settings configuration.
     *
     * Reads prefix, format, padding, and next number from the settings,
     * then auto-increments the stored next number.
     */
    public static function generateInvoiceNumber(): string
    {
        $settings = \TheWebsiteGuy\NexusCRM\Models\Settings::instance();

        $prefix  = trim($settings->invoice_prefix ?: 'INV');
        $format  = $settings->invoice_number_format ?: 'prefix-date-seq';
        $padding = (int) ($settings->invoice_number_padding ?: 4);
        $nextNum = (int) ($settings->invoice_next_number ?: 1);

        $seq  = str_pad($nextNum, $padding, '0', STR_PAD_LEFT);
        $date = date('Ymd');

        switch ($format) {
            case 'prefix-seq':
                $number = "{$prefix}-{$seq}";
                break;
            case 'date-seq':
                $number = "{$date}-{$seq}";
                break;
            case 'seq-only':
                $number = $seq;
                break;
            case 'prefix-date-seq':
            default:
                $number = "{$prefix}-{$date}-{$seq}";
                break;
        }

        // Increment the stored next number
        $settings->invoice_next_number = $nextNum + 1;
        $settings->save();

        return $number;
    }

    // ────────────────────────────────────────────────────────────
    //  Dropdown Options
    // ────────────────────────────────────────────────────────────

    /**
     * Options for the internal_status dropdown (backend form).
     */
    public function getInternalStatusOptions(): array
    {
        return [
            self::INTERNAL_DRAFT => 'Draft',
            self::INTERNAL_SENT  => 'Sent',
        ];
    }

    /**
     * Options for the client-facing status dropdown (backend form).
     */
    public function getStatusOptions(): array
    {
        return [
            self::STATUS_OUTSTANDING => 'Outstanding',
            self::STATUS_DUE         => 'Due',
            self::STATUS_OVERDUE     => 'Overdue',
            self::STATUS_CANCELLED   => 'Cancelled',
            self::STATUS_PAID        => 'Paid',
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  Scopes
    // ────────────────────────────────────────────────────────────

    /**
     * Scope to only include invoices visible to clients (sent, not draft).
     */
    public function scopeClientVisible($query)
    {
        return $query->where('internal_status', self::INTERNAL_SENT);
    }

    /**
     * Scope to only include draft invoices.
     */
    public function scopeDraft($query)
    {
        return $query->where('internal_status', self::INTERNAL_DRAFT);
    }
}
