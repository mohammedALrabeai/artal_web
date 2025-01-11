<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_number',
        'entity_name',
        'city',
        'entity_type',
        'capital',
        'insurance_number',
        'labour_office_number',
        'unified_number',
        'expiry_date_hijri',
        'expiry_date_gregorian',
        'tax_authority_number',
        'remaining_days',
        'vat',
        'parent_company_id',
    ];

    // علاقة مع الشركة الأم
    public function parentCompany()
    {
        return $this->belongsTo(CommercialRecord::class, 'parent_company_id');
    }

    // علاقة مع الشركات الفرعية
    public function childCompanies()
    {
        return $this->hasMany(CommercialRecord::class, 'parent_company_id');
    }

    // علاقة مع التراخيص الخاصة
    public function privateLicenses()
    {
        return $this->hasMany(PrivateLicense::class, 'commercial_record_id');
    }

    // علاقة مع رخص البلديات
    public function municipalLicenses()
    {
        return $this->hasMany(MunicipalLicense::class, 'commercial_record_id');
    }

    // علاقة مع اشتراكات البريد
    public function postalSubscriptions()
    {
        return $this->hasMany(PostalSubscription::class, 'commercial_record_id');
    }

    // علاقة مع العنوان الوطني
    public function nationalAddresses()
    {
        return $this->hasMany(NationalAddress::class, 'commercial_record_id');
    }

    public function employees()
{
    return $this->hasMany(Employee::class, 'commercial_record_id');
}

}
