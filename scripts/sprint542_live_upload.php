<?php

use App\Domains\Branding\Models\Brand;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Sales\Products\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$company = Company::query()->where('name', 'like', '%nica%')->first()
    ?? Company::query()->skip(1)->first()
    ?? Company::query()->first();
if (! $company) {
    fwrite(STDERR, "NO_COMPANY\n");
    exit(1);
}

$admin = User::query()
    ->where('company_id', $company->id)
    ->whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))
    ->first()
    ?? User::query()->where('company_id', $company->id)->first();

if (! $admin) {
    fwrite(STDERR, "NO_ADMIN\n");
    exit(1);
}

echo 'USER='.$admin->email.PHP_EOL;

$media = app(MediaUploadService::class);

$makePng = function (string $name, int $w, int $h, int $r, int $g, int $b): UploadedFile {
    $tmp = tempnam(sys_get_temp_dir(), 'up');
    $img = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($img, $r, $g, $b);
    imagefilledrectangle($img, 0, 0, $w, $h, $bg);
    imagepng($img, $tmp);
    imagedestroy($img);

    return new UploadedFile($tmp, $name, 'image/png', null, true);
};

$logo = $media->store(
    $makePng('logo-live.png', 120, 60, 229, 57, 53),
    (int) $company->id,
    MediaCategory::Branding,
    MediaPurpose::Logo,
);

$brand = Brand::query()->withoutGlobalScopes()->firstOrNew(['company_id' => $company->id]);
$brand->fill([
    'system_name' => $brand->system_name ?: 'GeoSales CRM',
    'display_name' => $brand->display_name ?: $company->name,
    'theme' => $brand->theme?->value ?? 'dark',
    'logo' => $logo->path,
    'logo_mark' => $logo->path,
]);
$brand->company_id = $company->id;
$brand->save();

$photo = $media->store(
    $makePng('avatar-live.png', 128, 128, 59, 130, 246),
    (int) $company->id,
    MediaCategory::Profiles,
    MediaPurpose::ProfilePhoto,
    $admin->photo,
    $admin->photo_thumb,
    'photo',
);
$admin->update(['photo' => $photo->path, 'photo_thumb' => $photo->thumbPath]);

$product = Product::query()->where('company_id', $company->id)->first();
if ($product) {
    $image = $media->store(
        $makePng('product-live.png', 180, 180, 34, 197, 94),
        (int) $company->id,
        MediaCategory::Products,
        MediaPurpose::ProductImage,
        $product->image,
        $product->image_thumb,
        'image',
    );
    $product->update(['image' => $image->path, 'image_thumb' => $image->thumbPath]);
}

echo 'COMPANY='.$company->id.PHP_EOL;
echo 'LOGO_PATH='.$logo->path.PHP_EOL;
echo 'LOGO_URL='.$media->url($logo->path).PHP_EOL;
echo 'LOGO_PUBLIC='.(file_exists(public_path('storage/'.$logo->path)) ? '1' : '0').PHP_EOL;
echo 'PHOTO_PATH='.$photo->path.PHP_EOL;
echo 'PHOTO_URL='.$media->url($photo->thumbPath ?: $photo->path).PHP_EOL;
echo 'PHOTO_PUBLIC='.(file_exists(public_path('storage/'.($photo->thumbPath ?: $photo->path))) ? '1' : '0').PHP_EOL;
if ($product) {
    echo 'PRODUCT_PATH='.$product->fresh()->image.PHP_EOL;
    echo 'PRODUCT_URL='.$product->fresh()->imageUrl().PHP_EOL;
    echo 'PRODUCT_PUBLIC='.(file_exists(public_path('storage/'.$product->fresh()->image)) ? '1' : '0').PHP_EOL;
}
echo 'STORAGE_EXISTS='.(Storage::disk('public')->exists($logo->path) ? '1' : '0').PHP_EOL;
