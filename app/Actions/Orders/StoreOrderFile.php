<?php

namespace App\Actions\Orders;

use App\Enums\OrderFileKind;
use App\Models\AcademicOrder;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Saves an uploaded file to the private order-files disk. The caller has
 * already validated the type (by content, not by name) and size.
 */
class StoreOrderFile
{
    public function handle(AcademicOrder $order, UploadedFile $upload, OrderFileKind $kind, User $by): OrderFile
    {
        $disk = (string) config('academic.files_disk');
        $extension = array_search($upload->getMimeType(), config('academic.mimes'), true) ?: 'bin';
        $path = $upload->storeAs("orders/{$order->id}", Str::uuid().'.'.$extension, ['disk' => $disk]);

        $file = new OrderFile;
        $file->forceFill([
            'academic_order_id' => $order->id,
            'uploaded_by' => $by->id,
            'kind' => $kind,
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit($upload->getClientOriginalName(), 200, ''),
            'mime' => (string) $upload->getMimeType(),
            'size' => (int) $upload->getSize(),
        ])->save();

        return $file;
    }

    /**
     * Validation rules for one upload: PDF or DOCX (checked from the file's
     * content) up to 25 MB.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['file', 'mimetypes:'.implode(',', config('academic.mimes')), 'max:'.config('academic.max_upload_kb')];
    }
}
