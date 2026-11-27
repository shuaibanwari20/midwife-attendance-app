<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyWorkReport extends Model
{
    protected $fillable = [
        'midwife_id', 'month', 'status', 'payload', 'totals',
        'supervisor_note', 'register_photo', 'submitted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'totals' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public const STATUSES = ['draft', 'submitted', 'returned', 'approved'];

    public const STATUS_LABELS = [
        'draft' => 'پیش‌نویس',
        'submitted' => 'در انتظار بررسی',
        'returned' => 'برگشت‌خورده',
        'approved' => 'تأییدشده',
        'missing' => 'نرسیده',
    ];

    /**
     * The form definition — one shared source for the API validation,
     * the app form and the admin panel. Sections in display order;
     * every field is a non-negative integer counter unless noted.
     */
    public const SECTIONS = [
        'general' => [
            'label' => 'معلومات عمومی',
            'fields' => [
                'working_days' => 'روزهای کاری در ماه',
                'villages_visited' => 'قریه‌های بازدیدشده',
                'vacc_sessions' => 'جلسات واکسیناسیون (ثابت و سیار)',
            ],
        ],
        'vacc' => [
            'label' => 'واکسیناسیون و OPV',
            'fields' => [
                'bcg' => 'BCG (سل)',
                'opv0' => 'OPV0 (هنگام تولد)',
                'opv1' => 'OPV1',
                'opv2' => 'OPV2',
                'opv3' => 'OPV3',
                'penta1' => 'پنتاوالنت 1',
                'penta2' => 'پنتاوالنت 2',
                'penta3' => 'پنتاوالنت 3',
                'pcv1' => 'PCV 1',
                'pcv2' => 'PCV 2',
                'pcv3' => 'PCV 3',
                'rota1' => 'روتاویروس 1',
                'rota2' => 'روتاویروس 2',
                'rota3' => 'روتاویروس 3',
                'measles1' => 'سرخکان 1',
                'measles2' => 'سرخکان 2',
                'tt_pregnant' => 'تیتانوس — زنان حامله',
                'tt_women' => 'تیتانوس — زنان سن باروری',
                'fully_immunized' => 'اطفال کاملاً واکسین‌شده',
                'campaign_days' => 'روزهای کمپاین پولیو',
                'campaign_children' => 'اطفال واکسین‌شده در کمپاین',
            ],
        ],
        'opd' => [
            'label' => 'مراجعین سراپا (OPD)',
            'fields' => [
                'opd_u5_m' => 'زیر 5 سال — پسر',
                'opd_u5_f' => 'زیر 5 سال — دختر',
                'opd_o5_m' => '5 سال و بالا — مرد',
                'opd_o5_f' => '5 سال و بالا — زن',
                'opd_women_1549' => 'از جمله: زنان 15 تا 49 سال',
                'opd_revisits' => 'مراجعین تکراری (پیگیری)',
                'referrals' => 'ارجاع به مرکز بالاتر',
                'health_sessions' => 'جلسات آگاهی‌دهی صحی',
                'health_participants' => 'اشتراک‌کنندگان آگاهی‌دهی',
                'diarrhea' => 'واقعات اسهال',
                'ari' => 'عفونت تنفسی / سینه‌بغل',
                'malaria_suspected' => 'ملاریای مشکوک',
            ],
        ],
        'fp' => [
            'label' => 'تنظیم خانواده',
            'fields' => [
                'fp_counseling' => 'مشوره تنظیم خانواده',
                'pill_new' => 'تابلیت — مراجع جدید',
                'pill_revisit' => 'تابلیت — مراجع تکراری',
                'inj_new' => 'پیچکاری — مراجع جدید',
                'inj_revisit' => 'پیچکاری — مراجع تکراری',
                'condom_new' => 'کاندوم — مراجع جدید',
                'condom_revisit' => 'کاندوم — مراجع تکراری',
                'condoms_distributed' => 'کاندوم توزیع‌شده (عدد)',
                'iud_new' => 'IUD — مراجع جدید',
                'iud_revisit' => 'IUD — مراجع تکراری',
                'implant_new' => 'ایمپلنت — مراجع جدید',
                'implant_revisit' => 'ایمپلنت — مراجع تکراری',
                'fp_referrals' => 'ارجاع تنظیم خانواده',
            ],
        ],
        'maternal' => [
            'label' => 'صحت مادر و طفل و تغذی',
            'fields' => [
                'anc1' => 'مراقبت حاملگی — مراجعه اول',
                'anc4' => 'مراقبت حاملگی — مراجعه چهارم',
                'pnc' => 'مراقبت بعد از ولادت',
                'deliveries' => 'ولادت‌های کمک‌شده',
                'muac_screened' => 'اطفال غربال‌شده با MUAC',
                'malnutrition_found' => 'سوءتغذی شناسایی‌شده',
                'malnutrition_referred' => 'سوءتغذی ارجاع‌شده',
                'vit_a' => 'ویتامین A تطبیق‌شده',
            ],
        ],
        'stock' => [
            'label' => 'موجودی و مصرف',
            'fields' => [
                'pill_opening' => 'تابلیت — موجودی اول ماه (سایکل)',
                'pill_received' => 'تابلیت — دریافت‌شده',
                'pill_used' => 'تابلیت — مصرف‌شده',
                'inj_opening' => 'پیچکاری — موجودی اول ماه (دوز)',
                'inj_received' => 'پیچکاری — دریافت‌شده',
                'inj_used' => 'پیچکاری — مصرف‌شده',
                'condom_opening' => 'کاندوم — موجودی اول ماه (عدد)',
                'condom_received' => 'کاندوم — دریافت‌شده',
                'condom_used' => 'کاندوم — مصرف‌شده',
                'iud_opening' => 'IUD — موجودی اول ماه',
                'iud_received' => 'IUD — دریافت‌شده',
                'iud_used' => 'IUD — مصرف‌شده',
                'implant_opening' => 'ایمپلنت — موجودی اول ماه',
                'implant_received' => 'ایمپلنت — دریافت‌شده',
                'implant_used' => 'ایمپلنت — مصرف‌شده',
                'opv_vials_used' => 'ویال OPV مصرف‌شده',
                'bcg_vials_used' => 'ویال BCG مصرف‌شده',
                'penta_vials_used' => 'ویال پنتاوالنت مصرف‌شده',
                'measles_vials_used' => 'ویال سرخکان مصرف‌شده',
                'doses_wasted' => 'دوزهای ضایع‌شده واکسین',
            ],
        ],
    ];

    /** Antigen dose keys summed into the total vaccine-doses figure. */
    public const DOSE_KEYS = [
        'bcg', 'opv0', 'opv1', 'opv2', 'opv3',
        'penta1', 'penta2', 'penta3', 'pcv1', 'pcv2', 'pcv3',
        'rota1', 'rota2', 'rota3', 'measles1', 'measles2',
        'tt_pregnant', 'tt_women',
    ];

    /** Stock item prefixes with an opening/received/used triple. */
    public const STOCK_ITEMS = ['pill', 'inj', 'condom', 'iud', 'implant'];

    /** Keys that make sense as daily quick-entry counters. */
    public static function dailyKeys(): array
    {
        $skip = ['working_days', 'villages_visited'];
        foreach (self::STOCK_ITEMS as $item) {
            $skip[] = $item . '_opening';
            $skip[] = $item . '_received';
            $skip[] = $item . '_used';
        }
        $skip = array_merge($skip, ['opv_vials_used', 'bcg_vials_used', 'penta_vials_used', 'measles_vials_used', 'doses_wasted']);

        return array_values(array_diff(self::numericKeys(), $skip));
    }

    /** All numeric payload keys across every section. */
    public static function numericKeys(): array
    {
        $keys = [];
        foreach (self::SECTIONS as $section) {
            foreach ($section['fields'] as $key => $_label) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /** Keep only known keys; counters coerced to non-negative ints. */
    public static function cleanPayload(array $input): array
    {
        $clean = [];
        foreach (self::numericKeys() as $key) {
            if (array_key_exists($key, $input) && $input[$key] !== null && $input[$key] !== '') {
                $clean[$key] = max(0, min(1000000, (int) $input[$key]));
            }
        }
        if (isset($input['problems']) && is_string($input['problems'])) {
            $clean['problems'] = mb_substr(trim($input['problems']), 0, 2000);
        }

        return $clean;
    }

    /** Derived figures stored with the report and used by the panel. */
    public static function computeTotals(array $p): array
    {
        $v = fn (string $k) => (int) ($p[$k] ?? 0);

        $doses = 0;
        foreach (self::DOSE_KEYS as $k) {
            $doses += $v($k);
        }

        $stockClosing = [];
        foreach (self::STOCK_ITEMS as $item) {
            $stockClosing[$item] = $v($item . '_opening') + $v($item . '_received') - $v($item . '_used');
        }

        return [
            'opd_total' => $v('opd_u5_m') + $v('opd_u5_f') + $v('opd_o5_m') + $v('opd_o5_f'),
            'vacc_doses' => $doses,
            'opv_dropout_pct' => $v('opv1') > 0
                ? round(($v('opv1') - $v('opv3')) * 100 / $v('opv1'), 1)
                : null,
            'fp_new' => $v('pill_new') + $v('inj_new') + $v('condom_new') + $v('iud_new') + $v('implant_new'),
            'fp_revisit' => $v('pill_revisit') + $v('inj_revisit') + $v('condom_revisit') + $v('iud_revisit') + $v('implant_revisit'),
            'stock_closing' => $stockClosing,
        ];
    }

    /** Non-blocking consistency warnings shown to the midwife + panel. */
    public static function warnings(array $p): array
    {
        $v = fn (string $k) => (int) ($p[$k] ?? 0);
        $out = [];
        if ($v('opv3') > $v('opv1')) {
            $out[] = 'OPV3 از OPV1 بیشتر است؛ لطفاً بررسی کنید.';
        }
        if ($v('penta3') > $v('penta1')) {
            $out[] = 'پنتاوالنت 3 از پنتاوالنت 1 بیشتر است؛ لطفاً بررسی کنید.';
        }
        if ($v('opd_women_1549') > $v('opd_o5_f')) {
            $out[] = 'زنان 15 تا 49 سال از مجموع زنان 5 سال و بالا بیشتر است.';
        }
        if ($v('malnutrition_referred') > $v('malnutrition_found')) {
            $out[] = 'سوءتغذی ارجاع‌شده از شناسایی‌شده بیشتر است.';
        }
        foreach (self::STOCK_ITEMS as $item) {
            if ($v($item . '_used') > $v($item . '_opening') + $v($item . '_received')) {
                $out[] = 'مصرف یکی از اقلام تنظیم خانواده از موجودی آن بیشتر است.';
                break;
            }
        }

        return $out;
    }

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(Midwife::class);
    }
}
