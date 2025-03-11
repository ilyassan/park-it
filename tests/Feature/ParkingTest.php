<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Parking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkingTest extends TestCase
{
    // use RefreshDatabase;

    /**********************
     * Admin Routes Tests
     **********************/

    /** @test */
    public function an_admin_can_create_a_parking()
    {
        $admin = User::factory()->create(['role_id' => RoleEnum::ADMIN]);
        $this->actingAs($admin, 'sanctum');

        // Create a parking
        $response = $this->postJson('/api/parkings/create', [
            'name' => 'Parking A',
            'price' => 10,
            'limit' => 50,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true,
                     'message' => 'Parking Created Successfully',
                 ]);

        $this->assertDatabaseHas('parkings', [
            'name' => 'Parking A',
            'price' => 10,
            'limit' => 50,
        ]);
    }

    /** @test */
    public function an_admin_cannot_create_a_parking_with_invalid_data()
    {
        $admin = User::factory()->create(['role_id' => RoleEnum::ADMIN]);
        $this->actingAs($admin, 'sanctum');

        // Attempt to create a parking with invalid data
        $response = $this->postJson('/api/parkings/create', [
            'name' => '',
            'price' => 'invalid',
            'limit' => 'invalid',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'price', 'limit']);
    }

    /** @test */
    public function an_admin_can_update_a_parking()
    {
        $admin = User::factory()->create(['role_id' => RoleEnum::ADMIN]);
        $this->actingAs($admin, 'sanctum');

        // Create a parking
        $parking = Parking::factory()->create();

        // Update the parking
        $response = $this->putJson("/api/parkings/{$parking->id}", [
            'name' => 'Updated Parking',
            'price' => 20,
            'limit' => 100,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true,
                     'message' => 'Parking Updated Successfully',
                 ]);

        $this->assertDatabaseHas('parkings', [
            'id' => $parking->id,
            'name' => 'Updated Parking',
            'price' => 20,
            'limit' => 100,
        ]);
    }

    /** @test */
    public function an_admin_cannot_update_a_parking_with_invalid_data()
    {
        $admin = User::factory()->create(['role_id' => RoleEnum::ADMIN]);
        $this->actingAs($admin, 'sanctum');

        // Create a parking
        $parking = Parking::factory()->create();

        $response = $this->putJson("/api/parkings/{$parking->id}", [
            'name' => '',
            'price' => 'invalid',
            'limit' => 'invalid',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'price', 'limit']);
    }

    /** @test */
    public function an_admin_can_delete_a_parking()
    {
        $admin = User::factory()->create(['role_id' => RoleEnum::ADMIN]);
        $this->actingAs($admin, 'sanctum');

        // Create a parking
        $parking = Parking::factory()->create();

        // Delete the parking
        $response = $this->deleteJson("/api/parkings/{$parking->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('parkings', [
            'id' => $parking->id,
        ]);
    }

    /** @test */
    public function an_admin_cannot_delete_a_non_existent_parking()
    {
        $admin = User::factory()->create(['role_id' => RoleEnum::ADMIN]);
        $this->actingAs($admin, 'sanctum');

        // Attempt to delete a non-existent parking
        $response = $this->deleteJson('/api/parkings/999');

        $response->assertStatus(404)
                 ->assertJson([
                     'status' => false,
                     'message' => 'Parking not found',
                 ]);
    }

    /**********************
     * Client Routes Tests
     **********************/

    /** @test */
    public function a_client_can_fetch_all_parkings()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        $count = Parking::count();

        Parking::factory()->count(3)->create();

        $response = $this->getJson('/api/parkings');

        $response->assertStatus(200)
                 ->assertJsonCount($count + 3);
    }

    /** @test */
    public function a_client_cannot_create_a_parking()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        $response = $this->postJson('/api/parkings/create', [
            'name' => 'Parking A',
            'price' => 10,
            'limit' => 50,
        ]);

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function a_client_cannot_update_a_parking()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        // Create a parking
        $parking = Parking::factory()->create();

        // Attempt to update the parking
        $response = $this->putJson("/api/parkings/{$parking->id}", [
            'name' => 'Updated Parking',
            'price' => 20,
            'limit' => 100,
        ]);

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function a_client_cannot_delete_a_parking()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        // Create a parking
        $parking = Parking::factory()->create();

        $response = $this->deleteJson("/api/parkings/{$parking->id}");

        $response->assertStatus(403); // Forbidden
    }
}