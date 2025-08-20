<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $employee_id
 * @property string $type           enroll|verify
 * @property string $disk           example: public, s3
 * @property string $path           example: employees/123/face/verify/20250820_101010.jpg
 * @property \Illuminate\Support\Carbon $captured_at
 * @property float|null $similarity
 * @property string|null $rek_face_id
 * @property string|null $rek_image_id
 * @property array|null $meta
 *
 * @property-read string|null $url
 * @property-read string|null $thumb_url
 * @property-read Employee $employee
 */
class EmployeeFaceEvent extends Model
{
    use HasFactory;

    /** أنواع السجل */
    public const TYPE_ENROLL = 'enroll';
    public const TYPE_VERIFY = 'verify';

    /** اسم الجدول (إن اختلف اسمك عن الافتراضي) */
    protected $table = 'employee_face_events';

    /** الحقول القابلة للتعبئة */
    protected $fillable = [
        'employee_id',
        'type',
        'disk',
        'path',
        'captured_at',
        'similarity',
        'rek_face_id',
        'rek_image_id',
        'meta',
    ];

    /** التحويلات */
    protected $casts = [
        'captured_at' => 'datetime',
        'similarity'  => 'decimal:2',
        'meta'        => 'array',
    ];

    /** إرفاق روابط العرض تلقائياً مع toArray/JSON */
    protected $appends = [
        'url',
        'thumb_url',
    ];

    /* ============================
     | علاقات
     * ============================ */

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /* ============================
     | Accessors (روابط وصيغ جاهزة)
     * ============================ */

    /**
     * رابط الصورة الكامل عبر القرص المحدد.
     * يُرجع null إن لم يكن هناك مسار.
     */
    public function url(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (!$this->path || !$this->disk) {
                return null;
            }
            return Storage::disk($this->disk)->url($this->path);
        });
    }

    /**
     * رابط نسخة مصغّرة (اختياري).
     * يعتمد على كونك تحفظ مصغّرات في مسار: ".../thumbs/{basename}".
     * عدّل المنطق حسب طريقتك في توليد المصغّرات.
     */
    public function thumbUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (!$this->path || !$this->disk) {
                return null;
            }

            // مثال تقليدي: وضع المصغّر داخل مجلد thumbs بنفس الاسم
            $dir  = trim(dirname($this->path), '/');
            $base = basename($this->path);
            $thumbPath = $dir . '/thumbs/' . $base;

            if (Storage::disk($this->disk)->exists($thumbPath)) {
                return Storage::disk($this->disk)->url($thumbPath);
            }

            // لو ما عندك مصغّرات، ارجع رابط الصورة الأصلية
            return Storage::disk($this->disk)->url($this->path);
        });
    }

    /* ============================
     | Helpers
     * ============================ */

    public function isEnroll(): bool
    {
        return $this->type === self::TYPE_ENROLL;
    }

    public function isVerify(): bool
    {
        return $this->type === self::TYPE_VERIFY;
    }

    /**
     * يرجع true إذا كان الالتقاط اليوم حسب منطقة زمنية (افتراضي: الرياض).
     */
    public function isToday(string $tz = 'Asia/Riyadh'): bool
    {
        return optional($this->captured_at)->setTimezone($tz)->isToday() ?? false;
    }

    /* ============================
     | Scopes للاستعلامات الشائعة
     * ============================ */

    /** سجلات موظف معيّن */
    public function scopeForEmployee(Builder $q, int $employeeId): Builder
    {
        return $q->where('employee_id', $employeeId);
    }

    /** فقط التسجيل */
    public function scopeEnrolls(Builder $q): Builder
    {
        return $q->where('type', self::TYPE_ENROLL);
    }

    /** فقط التحقق */
    public function scopeVerifies(Builder $q): Builder
    {
        return $q->where('type', self::TYPE_VERIFY);
    }

    /** اليوم الحالي حسب منطقة زمنية */
    public function scopeToday(Builder $q, string $tz = 'Asia/Riyadh'): Builder
    {
        $today = now($tz)->toDateString();
        return $q->whereDate('captured_at', $today);
    }

    /** بين تاريخين (شاملين) حسب منطقة زمنية */
    public function scopeBetweenDates(Builder $q, string|\DateTimeInterface $from, string|\DateTimeInterface $to, string $tz = 'Asia/Riyadh'): Builder
    {
        $fromAt = \Illuminate\Support\Carbon::parse($from, $tz)->startOfDay()->utc();
        $toAt   = \Illuminate\Support\Carbon::parse($to, $tz)->endOfDay()->utc();

        return $q->whereBetween('captured_at', [$fromAt, $toAt]);
    }

    /** الأحدث أولاً */
    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderByDesc('captured_at')->orderByDesc('id');
    }

    /** أحدث صورة تسجيل لِموظف */
    public function scopeLatestEnrollFor(Builder $q, int $employeeId): Builder
    {
        return $q->forEmployee($employeeId)->enrolls()->latestFirst()->limit(1);
    }

    /* ============================
     | Factories وتوليد قيَم افتراضية (اختياري)
     * ============================ */

    /**
     * في حال رغبت بتعيين قيم افتراضية عند الإنشاء.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // لو لم يُحدّد القرص، اجعله public
            if (empty($model->disk)) {
                $model->disk = 'public';
            }
            // لو لم يُحدَّد captured_at، اجعله الآن (UTC)
            if (empty($model->captured_at)) {
                $model->captured_at = now(); // يُخزن UTC
            }
        });
    }
}
