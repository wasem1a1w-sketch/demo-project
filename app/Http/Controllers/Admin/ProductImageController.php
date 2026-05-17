<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class ProductImageController extends Controller
{
    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);

        Storage::disk('public')->delete(array_filter([
            $image->image_path,
            $image->thumb_path,
            $image->icon_path,
        ]));

        $product = $image->product;

        UserActivityLog::record(auth()->id(), 'product_image_deleted', "Product image deleted for product: {$product?->name}");

        $image->delete();

        if ($product && $product->primary_image_id == $id) {
            $product->update(['primary_image_id' => null]);
        }

        return back();
    }
}
