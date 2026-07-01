<?php

use App\Models\Category;
use App\Models\City;
use App\Models\Property;

beforeEach(function () {
    $this->house = Category::factory()->create(['slug' => 'house']);
    $this->land  = Category::factory()->create(['slug' => 'land']);
    $this->ktm   = City::factory()->create(['public_id' => 53, 'name' => 'Kathmandu', 'slug' => 'kathmandu']);
    $this->ltp   = City::factory()->create(['public_id' => 54, 'name' => 'Lalitpur', 'slug' => 'lalitpur']);
});

it('returns only active properties to the public', function () {
    Property::factory()->active()->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id]);
    Property::factory()->pending()->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id]);

    $res = $this->getJson('/api/v1/properties');

    $res->assertOk();
    expect($res->json('data'))->toHaveCount(1);
    expect($res->json('meta.pagination.total'))->toBe(1);
});

it('filters by transaction_type', function () {
    Property::factory()->active()->buy()->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id]);
    Property::factory()->active()->rent()->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id]);

    expect($this->getJson('/api/v1/properties?transaction_type=rent')->json('data'))->toHaveCount(1);
    expect($this->getJson('/api/v1/properties?transaction_type=buy')->json('data'))->toHaveCount(1);
});

it('filters by category slug and city public_id', function () {
    Property::factory()->active()->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id]);
    Property::factory()->active()->create(['category_id' => $this->land->id,  'city_id' => $this->ltp->id]);

    expect($this->getJson('/api/v1/properties?category=house')->json('data'))->toHaveCount(1);
    expect($this->getJson('/api/v1/properties?city=53')->json('data'))->toHaveCount(1);
    expect($this->getJson('/api/v1/properties?category=land&city=54')->json('data'))->toHaveCount(1);
    expect($this->getJson('/api/v1/properties?category=house&city=54')->json('data'))->toHaveCount(0);
});

it('filters by price range', function () {
    Property::factory()->active()->create(['price' => 5_000_000, 'category_id' => $this->house->id, 'city_id' => $this->ktm->id]);
    Property::factory()->active()->create(['price' => 50_000_000, 'category_id' => $this->house->id, 'city_id' => $this->ktm->id]);

    expect($this->getJson('/api/v1/properties?min_price=10000000')->json('data'))->toHaveCount(1);
    expect($this->getJson('/api/v1/properties?max_price=10000000')->json('data'))->toHaveCount(1);
});

it('sorts by price ascending and descending', function () {
    Property::factory()->active()->create(['price' => 10, 'category_id' => $this->house->id, 'city_id' => $this->ktm->id]);
    Property::factory()->active()->create(['price' => 99, 'category_id' => $this->house->id, 'city_id' => $this->ktm->id]);

    $asc = $this->getJson('/api/v1/properties?sort=price_asc')->json('data');
    expect($asc[0]['price']['amount'])->toBe(10.0);

    $desc = $this->getJson('/api/v1/properties?sort=price_desc')->json('data');
    expect($desc[0]['price']['amount'])->toBe(99.0);
});

it('paginates with per_page', function () {
    Property::factory()->active()->count(15)->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id]);

    $res = $this->getJson('/api/v1/properties?per_page=5');
    expect($res->json('data'))->toHaveCount(5);
    expect($res->json('meta.pagination.last_page'))->toBe(3);
});

it('returns featured section listings only', function () {
    Property::factory()->active()->featured()->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id]);
    Property::factory()->active()->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id]);

    expect($this->getJson('/api/v1/properties/featured')->json('data'))->toHaveCount(1);
});

it('increments views on detail view', function () {
    $p = Property::factory()->active()->create(['category_id' => $this->house->id, 'city_id' => $this->ktm->id, 'views' => 0]);

    $this->getJson("/api/v1/properties/{$p->slug}")->assertOk();
    expect(Property::find($p->id)->views)->toBe(1);
});
