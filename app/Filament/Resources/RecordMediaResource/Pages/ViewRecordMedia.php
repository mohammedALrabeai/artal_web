<?php

namespace App\Filament\Resources\RecordMediaResource\Pages;

use App\Filament\Resources\RecordMediaResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRecordMedia extends ViewRecord
{
    protected static string $resource = RecordMediaResource::class;

    public function getHeaderActions(): array
    {
        $media = $this->record->getFirstMedia('record_media');

        if (! $media) {
            return [];
        }

        $fileUrl = $media->getTemporaryUrl(now()->addMinutes(30));

        return [
            Actions\Action::make('view_full')
                ->label(__('🔍 View Full'))
                ->url($fileUrl)
                ->openUrlInNewTab(),

            Actions\Action::make('download')
                ->label(__('⬇ Download'))
                ->url($fileUrl)
                ->openUrlInNewTab(), // ✅ جعل التحميل يعمل من خلال المتصفح
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Attachment Details'))
                    ->schema([
                        TextEntry::make('title')->label(__('Title')),
                        TextEntry::make('notes')->label(__('Notes')),
                        TextEntry::make('expiry_date')->label(__('Expiry Date'))->date(),
                        $this->getFilePreviewSection(),
                    ]),

                $this->getRelatedModelDetails(),
            ]);
    }

    protected function getFilePreviewSection(): Section
    {
        $media = $this->record->getFirstMedia('record_media');

        if (! $media) {
            return Section::make(__('File Preview'))->schema([
                TextEntry::make('no_file')->label(__('No file available')),
            ]);
        }

        $fileUrl = $media->getTemporaryUrl(now()->addMinutes(30));
        $fileType = $media->mime_type;

        $previewSchema = [];

        if (in_array($fileType, ['image/png', 'image/jpeg', 'image/gif'])) {
            array_push($previewSchema, SpatieMediaLibraryImageEntry::make('record_media')
                ->label(__('Image Preview'))
                ->collection('record_media')
                ->disk('s3')
                ->conversion('thumb'));
        } elseif ($fileType === 'application/pdf') {
            array_push($previewSchema, TextEntry::make('pdf_preview')
            ->label(__('📄 PDF Preview'))
            ->state(fn () => view('components.pdf-viewer', ['fileUrl' => $fileUrl]))
            ->html());
        } elseif (in_array($fileType, ['video/mp4', 'video/mpeg'])) {
            array_push($previewSchema, TextEntry::make('video_preview')->label(__('Video Preview'))
                ->state('<video class="w-full rounded" controls><source src="'.$fileUrl.'" type="'.$fileType.'">'.__('Your browser does not support the video tag.').'</video>')
                ->html());
        } else {
            array_push($previewSchema, TextEntry::make('file_info')->label(__('File Type'))
                ->state(__('This file is not previewable, but you can download it.')));
        }

        return Section::make(__('File Preview'))->schema($previewSchema);
    }

    protected function getRelatedModelDetails(): Section
    {
        $recordable = $this->record->recordable;

        if (! $recordable) {
            return Section::make(__('Related Model'))->schema([
                TextEntry::make('no_data')->label(__('No related model found')),
            ]);
        }

        $modelType = class_basename($recordable::class);
        $section = Section::make(__('Related Model Details'));

        switch ($modelType) {
            case 'CommercialRecord':
                $section->schema([
                    TextEntry::make('record_number')->label(__('Record Number'))->state($recordable->record_number),
                    TextEntry::make('entity_name')->label(__('Entity Name'))->state($recordable->entity_name),
                    TextEntry::make('city')->label(__('City'))->state($recordable->city),
                    TextEntry::make('entity_type')->label(__('Entity Type'))->state($recordable->entity_type),
                    TextEntry::make('capital')->label(__('Capital'))->state($recordable->capital),
                    TextEntry::make('expiry_date_gregorian')->label(__('Expiry Date (Gregorian)'))->date()->state($recordable->expiry_date_gregorian),
                ]);
                break;

            case 'NationalAddress':
                $section->schema([
                    TextEntry::make('address')->label(__('Address'))->state($recordable->address ?? 'N/A'),
                    TextEntry::make('postal_code')->label(__('Postal Code'))->state($recordable->postal_code ?? 'N/A'),
                    TextEntry::make('expiry_date')->label(__('Expiry Date'))->date()->state($recordable->expiry_date),
                ]);
                break;

            case 'MunicipalLicense':
                $section->schema([
                    TextEntry::make('license_number')->label(__('License Number'))->state($recordable->license_number),
                    TextEntry::make('expiry_date_gregorian')->label(__('Expiry Date (Gregorian)'))->date()->state($recordable->expiry_date_gregorian),
                    TextEntry::make('vat')->label(__('VAT'))->state($recordable->vat),
                ]);
                break;

            case 'PostalSubscription':
                $section->schema([
                    TextEntry::make('subscription_number')->label(__('Subscription Number'))->state($recordable->subscription_number),
                    TextEntry::make('start_date')->label(__('Start Date'))->date()->state($recordable->start_date),
                    TextEntry::make('expiry_date')->label(__('Expiry Date'))->date()->state($recordable->expiry_date),
                    TextEntry::make('mobile_number')->label(__('Mobile Number'))->state($recordable->mobile_number),
                ]);
                break;

            case 'PrivateLicense':
                $section->schema([
                    TextEntry::make('license_name')->label(__('License Name'))->state($recordable->license_name),
                    TextEntry::make('license_number')->label(__('License Number'))->state($recordable->license_number),
                    TextEntry::make('issue_date')->label(__('Issue Date'))->date()->state($recordable->issue_date),
                    TextEntry::make('expiry_date')->label(__('Expiry Date'))->date()->state($recordable->expiry_date),
                    TextEntry::make('description')->label(__('Description'))->state($recordable->description),
                ]);
                break;

            case 'InsuranceCompany':
                $section->schema([
                    TextEntry::make('name')->label(__('Company Name'))->state($recordable->name),
                    TextEntry::make('activation_date')->label(__('Activation Date'))->date()->state($recordable->activation_date),
                    TextEntry::make('expiration_date')->label(__('Expiration Date'))->date()->state($recordable->expiration_date),
                    TextEntry::make('policy_number')->label(__('Policy Number'))->state($recordable->policy_number),
                    TextEntry::make('branch')->label(__('Branch'))->state($recordable->branch),
                    TextEntry::make('is_active')->label(__('Status'))->state($recordable->is_active ? __('Active') : __('Inactive')),
                ]);
                break;

            default:
                $section->schema([
                    TextEntry::make('no_data')->label(__('No additional details available for this model')),
                ]);
                break;
        }

        return $section;
    }
}
