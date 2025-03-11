<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Parking;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    // use RefreshDatabase;

    /**********************
     * Store Reservation Tests
     **********************/

    /** @test */
    public function a_client_can_create_a_reservation()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        $parking = Parking::factory()->create(['limit' => 2]);

        // Create a reservation
        $response = $this->postJson('/api/reservations/create', [
            'from_date' => '2023-10-01 10:00:00',
            'to_date' => '2023-10-01 12:00:00',
            'parking_id' => $parking->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'from_date',
                     'to_date',
                     'user_id',
                     'parking_id',
                     'created_at',
                     'updated_at',
                 ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $client->id,
            'parking_id' => $parking->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function a_client_cannot_create_a_reservation_if_parking_is_full()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        $parking = Parking::factory()->create(['limit' => 1]);

        Reservation::create([
            'parking_id' => $parking->id,
            'from_date' => '2023-10-01 10:00:00',
            'to_date' => '2023-10-01 12:00:00',
            'user_id' => $client->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/reservations/create', [
            'from_date' => '2023-10-01 11:00:00',
            'to_date' => '2023-10-01 13:00:00',
            'parking_id' => $parking->id,
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'status' => false,
                     'message' => 'Parking is full',
                 ]);
    }

    /**********************
     * Update Reservation Tests
     **********************/

    /** @test */
    public function a_client_can_update_a_reservation()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        // Create a parking
        $parking = Parking::factory()->create(['limit' => 2]);

        $reservation = Reservation::create([
            'user_id' => $client->id,
            'parking_id' => $parking->id,
            'from_date' => '2023-10-01 10:00:00',
            'to_date' => '2023-10-01 12:00:00',
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/reservations/{$reservation->id}", [
            'from_date' => '2023-10-01 11:00:00',
            'to_date' => '2023-10-01 13:00:00',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true,
                     'message' => 'Reservation Updated Successfully',
                 ]);

        // Ensure the reservation was updated in the database
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'from_date' => '2023-10-01 11:00:00',
            'to_date' => '2023-10-01 13:00:00',
        ]);
    }

    /** @test */
    public function a_client_cannot_update_a_reservation_if_parking_is_full()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        $parking = Parking::factory()->create(['limit' => 1]);

        $reservation = Reservation::create([
            'user_id' => $client->id,
            'parking_id' => $parking->id,
            'from_date' => '2023-10-01 10:00:00',
            'to_date' => '2023-10-01 12:00:00',
            'status' => 'pending',
        ]);

        // Create another reservation that fills the parking
        Reservation::create([
            'user_id' => $client->id,
            'parking_id' => $parking->id,
            'from_date' => '2023-10-01 11:00:00',
            'to_date' => '2023-10-01 13:00:00',
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/reservations/{$reservation->id}", [
            'from_date' => '2023-10-01 11:00:00',
            'to_date' => '2023-10-01 13:00:00',
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'status' => false,
                     'message' => 'Parking is full or dates overlap with other reservations',
                 ]);
    }

    /**********************
     * Cancel Reservation Tests
     **********************/

    /** @test */
    public function a_client_can_cancel_a_reservation()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        $parking = Parking::factory()->create(['limit' => 1]);

        $reservation = Reservation::create([
            'user_id' => $client->id,
            'parking_id' => $parking->id,
            'from_date' => '2023-10-01 10:00:00',
            'to_date' => '2023-10-01 12:00:00',
            'status' => 'pending',
        ]);

        // Cancel the reservation
        $response = $this->deleteJson("/api/reservations/{$reservation->id}");

        $response->assertStatus(200);

        // Ensure the reservation was canceled in the database
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'canceled',
        ]);
    }

    /** @test */
    public function a_client_cannot_cancel_another_clients_reservation()
    {
        // Create two client users
        $client1 = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $client2 = User::factory()->create(['role_id' => RoleEnum::CLIENT]);

        $parking = Parking::factory()->create(['limit' => 1]);

        // Authenticate as client1
        $this->actingAs($client1, 'sanctum');

        // Create a reservation for client2
        $reservation = Reservation::create([
            'user_id' => $client2->id,
            'parking_id' => $parking->id,
            'from_date' => '2023-10-01 10:00:00',
            'to_date' => '2023-10-01 12:00:00',
            'status' => 'pending',
        ]);

        // Attempt to cancel client2's reservation as client1
        $response = $this->deleteJson("/api/reservations/{$reservation->id}");

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => false,
                     'message' => 'Unauthorized',
                 ]);
    }

    /**********************
     * My Reservations Tests
     **********************/

    /** @test */
    public function a_client_can_fetch_their_reservations()
    {
        $client = User::factory()->create(['role_id' => RoleEnum::CLIENT]);
        $this->actingAs($client, 'sanctum');

        
        $parking = Parking::factory()->create(['limit' => 1]);

        $data = [
            'user_id' => $client->id,
            'parking_id' => $parking->id,
            'from_date' => '2023-10-01 10:00:00',
            'to_date' => '2023-10-01 12:00:00',
            'status' => 'pending',
        ];
        // Create a reservation for client2
        Reservation::create($data);
        $data['to_date'] = '2023-10-01 14:00:00';
        Reservation::create($data);

        // Fetch the client's reservations
        $response = $this->getJson('/api/my-reservations');

        $response->assertStatus(200)
                 ->assertJsonCount(2); // Expect 2 reservations in the response
    }
}