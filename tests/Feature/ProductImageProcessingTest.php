<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductImageProcessingTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(\Imagick::class)) {
            $this->markTestSkipped('Imagick extension not available');
        }

        $this->category = Category::factory()->create(['is_active' => true]);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $neededPermissions = [
            'admin.access', 'products.create', 'products.read',
        ];
        foreach ($neededPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::pluck('id')->toArray());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function imageData(string $format = 'jpeg', int $width = 800, int $height = 600): UploadedFile
    {
        $img = new \Imagick();
        $img->newImage($width, $height, new \ImagickPixel('red'));
        $img->setImageFormat($format);

        $path = tempnam(sys_get_temp_dir(), 'imgtest') . '.' . $format;
        file_put_contents($path, $img->getImageBlob());
        $img->clear();

        return new UploadedFile($path, 'test.' . $format, mime_content_type($path), null, true);
    }

    public function test_upload_creates_all_three_webp_variants(): void
    {
        $file = $this->imageData('jpeg', 1200, 800);

        $url = route('admin.products.store');
        $response = $this->actingAs($this->admin)->post($url, [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $response->assertSessionHasNoErrors();

        $product = Product::where('slug', 'test-product')->first();
        $this->assertNotNull($product);

        $image = $product->images()->first();
        $this->assertNotNull($image);
        $this->assertNotNull($image->image_path);
        $this->assertNotNull($image->thumb_path);
        $this->assertNotNull($image->icon_path);
        $this->assertStringEndsWith('.webp', $image->image_path);
        $this->assertStringEndsWith('.webp', $image->thumb_path);
        $this->assertStringEndsWith('.webp', $image->icon_path);

        $this->assertFileExists(public_path($image->image_path));
        $this->assertFileExists(public_path($image->thumb_path));
        $this->assertFileExists(public_path($image->icon_path));
    }

    public function test_full_image_does_not_exceed_1920px(): void
    {
        $file = $this->imageData('jpg', 3000, 2000);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $image = Product::where('slug', 'test-product')->first()->images()->first();
        $img = new \Imagick(public_path($image->image_path));
        $this->assertLessThanOrEqual(1920, $img->getImageWidth());
        $this->assertLessThanOrEqual(1920, $img->getImageHeight());
        $img->clear();
    }

    public function test_thumbnail_does_not_exceed_400px(): void
    {
        $file = $this->imageData('jpeg', 1200, 800);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $image = Product::where('slug', 'test-product')->first()->images()->first();
        $img = new \Imagick(public_path($image->thumb_path));
        $this->assertLessThanOrEqual(400, $img->getImageWidth());
        $this->assertLessThanOrEqual(400, $img->getImageHeight());
        $img->clear();
    }

    public function test_icon_does_not_exceed_100px(): void
    {
        $file = $this->imageData('png', 800, 600);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $image = Product::where('slug', 'test-product')->first()->images()->first();
        $img = new \Imagick(public_path($image->icon_path));
        $this->assertLessThanOrEqual(100, $img->getImageWidth());
        $this->assertLessThanOrEqual(100, $img->getImageHeight());
        $img->clear();
    }

    public function test_icon_is_smaller_than_thumb(): void
    {
        $file = $this->imageData('jpeg', 1600, 1200);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $image = Product::where('slug', 'test-product')->first()->images()->first();
        $this->assertLessThan(
            filesize(public_path($image->thumb_path)),
            filesize(public_path($image->icon_path))
        );
    }

    public function test_png_is_converted_to_webp(): void
    {
        $file = $this->imageData('png', 500, 500);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $image = Product::where('slug', 'test-product')->first()->images()->first();
        $img = new \Imagick(public_path($image->image_path));
        $this->assertEquals('WEBP', $img->getImageFormat());
        $img->clear();
    }

    public function test_webp_upload_is_processed(): void
    {
        $file = $this->imageData('webp', 600, 400);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $image = Product::where('slug', 'test-product')->first()->images()->first();
        $this->assertNotNull($image);
        $this->assertNotNull($image->thumb_path);
        $this->assertNotNull($image->icon_path);
    }

    public function test_gallery_images_also_get_all_variants(): void
    {
        $file1 = $this->imageData('jpeg', 800, 600);
        $file2 = $this->imageData('png', 400, 300);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file1,
            'gallery_images' => [$file2],
        ]);

        $product = Product::where('slug', 'test-product')->first();
        $this->assertCount(2, $product->images);

        foreach ($product->images as $img) {
            $this->assertNotNull($img->image_path);
            $this->assertNotNull($img->thumb_path);
            $this->assertNotNull($img->icon_path);
        }
    }

    public function test_directory_structure_is_correct(): void
    {
        $file = $this->imageData('jpeg', 800, 600);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $image = Product::where('slug', 'test-product')->first()->images()->first();
        $this->assertStringStartsWith('uploads/original/', $image->image_path);
        $this->assertStringStartsWith('uploads/thumbnails/', $image->thumb_path);
        $this->assertStringStartsWith('uploads/icons/', $image->icon_path);
    }

    public function test_upload_keeps_aspect_ratio(): void
    {
        $file = $this->imageData('jpeg', 800, 400);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 19.99,
            'stock' => 10,
            'main_image' => $file,
        ]);

        $image = Product::where('slug', 'test-product')->first()->images()->first();
        $img = new \Imagick(public_path($image->image_path));
        $this->assertEquals(2.0, round($img->getImageWidth() / $img->getImageHeight(), 1));
        $img->clear();
    }
}
