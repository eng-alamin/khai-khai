<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * php artisan db:seed --class=CategorySeeder
     *
     * Safe to re-run (slug diye updateOrCreate). Tree structure:
     *   Category -> Sub Category -> Child Category   (max 3 level)
     *
     * type=food    -> restaurant/foods er jonno
     * type=product -> admin homepage products er jonno
     * type=bazar   -> দৈনন্দিন বাজার
     * type=service -> সেবা (চাকরি, জমি, ইন্টারনেট, বাসা, মেস)
     *
     * Bazar/Service er sub/child gulo sample — admin panel theke edit/add kora jabe.
     */
    public function run(): void
    {
        // Slug seeder-ei fixed deya hoy; model-er "name change hole slug regenerate"
        // hook ta seeder-e cholbe na.
        Category::withoutEvents(function () {
            $this->seedFoodAndProduct();
            $this->seedTree(self::bazarTree(), Category::TYPE_BAZAR);
            $this->seedTree(self::serviceTree(), Category::TYPE_SERVICE);
        });
    }

    /* ─────────────────────────────────────────────
     |  Food + Product (aager moto, kono change nai)
     ───────────────────────────────────────────── */
    private function seedFoodAndProduct(): void
    {
        $categories = [
            // Food categories — restaurant/foods er jonno
            ['type' => 'food', 'name' => 'Biryani & Rice'],
            ['type' => 'food', 'name' => 'Fast Food'],
            ['type' => 'food', 'name' => 'Desserts'],
            ['type' => 'food', 'name' => 'Beverages'],
            ['type' => 'food', 'name' => 'Curry & Bhaji'],

            // Product categories — admin homepage products er jonno
            ['type' => 'product', 'name' => 'Combo Deals'],
            ['type' => 'product', 'name' => 'Snacks'],
            ['type' => 'product', 'name' => 'Bakery'],
            ['type' => 'product', 'name' => 'Frozen Items'],
            ['type' => 'product', 'name' => 'Grocery'],
        ];

        foreach ($categories as $sortOrder => $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['type'] . '-' . $category['name'])],
                [
                    'type'       => $category['type'],
                    'name'       => $category['name'],
                    'image_url'  => null,
                    'sort_order' => $sortOrder,
                    'is_active'  => true,
                ]
            );
        }
    }

    /* ─────────────────────────────────────────────
     |  Recursive tree seeder
     |  slug = <parent slug>-<key>   (root: <type>-<key>)
     ───────────────────────────────────────────── */
    private function seedTree(array $nodes, string $type, ?Category $parent = null, ?string $slugPrefix = null): void
    {
        $slugPrefix ??= $type;

        foreach (array_values($nodes) as $index => $node) {
            $slug = $slugPrefix . '-' . $node['key'];

            $category = Category::withTrashed()->firstOrNew(['slug' => $slug]);

            // Structure + naam refresh hobe; admin-er deya image / active status
            // (already thakle) overwrite hobe na.
            $category->fill([
                'parent_id'  => $parent?->id,
                'type'       => $type,
                'name'       => $node['name'],
                'emoji'      => $node['emoji'] ?? null,
                'sort_order' => $index,
            ]);

            if (! $category->exists) {
                $category->is_active = true;
            }

            $category->save();

            if ($category->trashed()) {
                $category->restore();
            }

            if (! empty($node['children'])) {
                $this->seedTree($node['children'], $type, $category, $slug);
            }
        }
    }

    /** Short helper: node(key, naam, emoji, children) */
    private static function node(string $key, string $name, ?string $emoji = null, array $children = []): array
    {
        return ['key' => $key, 'name' => $name, 'emoji' => $emoji, 'children' => $children];
    }

    /* ─────────────────────────────────────────────
     |  বাজার
     ───────────────────────────────────────────── */
    private static function bazarTree(): array
    {
        $n = fn (...$a) => self::node(...$a);

        return [
            $n('grocery', 'মুদি বাজার', '🛒', [
                $n('oil-spices', 'তেল-মসলা', null, [
                    $n('oil', 'তেল'),
                    $n('spices', 'মসলা'),
                    $n('salt-sugar', 'লবণ-চিনি'),
                ]),
                $n('snacks-drinks', 'নাস্তা ও পানীয়', null, [
                    $n('biscuit', 'বিস্কুট'),
                    $n('tea-coffee', 'চা-কফি'),
                    $n('juice', 'জুস'),
                ]),
                $n('household', 'ঘর-গৃহস্থালি', null, [
                    $n('cleaning', 'পরিষ্কারের জিনিস'),
                    $n('personal-care', 'পারসোনাল কেয়ার'),
                ]),
            ]),

            $n('fresh', 'কাঁচা বাজার', '🥬', [
                $n('vegetables', 'সবজি', null, [
                    $n('leafy', 'শাক'),
                    $n('seasonal', 'মৌসুমি সবজি'),
                    $n('potato-onion', 'আলু-পেঁয়াজ'),
                ]),
                $n('fruits', 'ফলমূল', null, [
                    $n('local-fruits', 'দেশি ফল'),
                    $n('imported-fruits', 'বিদেশি ফল'),
                ]),
                $n('egg-dairy', 'ডিম ও দুধ'),
            ]),

            $n('meat-fish', 'মাছ-মাংস', '🐟', [
                $n('fish', 'মাছ', null, [
                    $n('river-fish', 'নদীর মাছ'),
                    $n('sea-fish', 'সামুদ্রিক মাছ'),
                    $n('farm-fish', 'চাষের মাছ'),
                ]),
                $n('meat', 'মাংস', null, [
                    $n('beef', 'গরুর মাংস'),
                    $n('mutton', 'খাসির মাংস'),
                    $n('chicken', 'মুরগি'),
                ]),
            ]),

            $n('rice-pulses', 'ডাল-চাল', '🌾', [
                $n('rice', 'চাল', null, [
                    $n('miniket', 'মিনিকেট'),
                    $n('nazirshail', 'নাজিরশাইল'),
                    $n('coarse-rice', 'মোটা চাল'),
                ]),
                $n('pulses', 'ডাল', null, [
                    $n('masoor', 'মসুর'),
                    $n('mung', 'মুগ'),
                    $n('chola', 'ছোলা'),
                ]),
                $n('flour', 'আটা-ময়দা'),
            ]),

            $n('pharmacy', 'ফার্মেসি', '💊', [
                $n('medicine', 'সাধারণ ওষুধ'),
                $n('vitamins', 'ভিটামিন ও সাপ্লিমেন্ট'),
                $n('health-care', 'স্বাস্থ্যসামগ্রী', null, [
                    $n('mask-sanitizer', 'মাস্ক ও স্যানিটাইজার'),
                    $n('baby-care', 'বেবি কেয়ার'),
                ]),
            ]),

            $n('custom-list', 'লিস্ট দিয়ে বাজার', '📝'),
        ];
    }

    /* ─────────────────────────────────────────────
     |  সেবা
     ───────────────────────────────────────────── */
    private static function serviceTree(): array
    {
        $n = fn (...$a) => self::node(...$a);

        $landKinds = fn () => [
            $n('residential', 'আবাসিক প্লট'),
            $n('agricultural', 'কৃষি জমি'),
            $n('commercial', 'বাণিজ্যিক জমি'),
        ];

        return [
            $n('jobs', 'চাকরি', '💼', [
                $n('full-time', 'ফুল-টাইম'),
                $n('part-time', 'পার্ট-টাইম'),
            ]),

            $n('land', 'জমি কেনা-বেচা', '🏞️', [
                $n('buy', 'জমি কিনুন', null, $landKinds()),
                $n('sell', 'জমি বিক্রি করুন', null, $landKinds()),
            ]),

            $n('broadband', 'ইন্টারনেট সংযোগ', '🌐', [
                $n('home', 'হোম ব্রডব্যান্ড'),
                $n('office', 'অফিস ব্রডব্যান্ড'),
            ]),

            $n('house-rent', 'বাসা ভাড়া', '🏠', [
                $n('flat', 'ফ্ল্যাট', null, [
                    $n('one-room', '১ রুম'),
                    $n('two-room', '২ রুম'),
                    $n('three-room', '৩ রুম'),
                ]),
                $n('room', 'রুম / সাবলেট'),
            ]),

            $n('mess', 'মেস', '🛏️', [
                $n('boys-mess', 'ছেলেদের মেস'),
                $n('girls-mess', 'মেয়েদের মেস'),
            ]),
        ];
    }
}
