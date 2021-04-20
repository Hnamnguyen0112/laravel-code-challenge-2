<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $token = $this->user->token();
        $data = [];
        for ($i = 0; $i < 3; $i++) {
            array_push($data, [
                'user_id' => $this->user->id,
                'number' => rand(1000000000000000, 9999999999999999),
                'expiration_date' => Carbon::now()->addYear(),
                'type' => 'Visa'
            ]);
        }
        DebitCard::insert($data);
        $response = $this->withHeaders([
            'Authorization' => $token,
        ])->get('/api/debit-cards');

        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $anotherUser = User::factory()->create();
        $data = [];
        for ($i = 0; $i < 3; $i++) {
            array_push($data, [
                'user_id' => $anotherUser->id,
                'number' => rand(1000000000000000, 9999999999999999),
                'expiration_date' => Carbon::now()->addYear(),
                'type' => 'Visa'
            ]);
        }
        DebitCard::insert($data);
        $debitCards = $anotherUser->debitCards->toArray();
        $token = $this->user->token();
        $response = $this->withHeaders([
            'Authorization' => $token,
        ])->get('/api/debit-cards');

        $response->assertStatus(200);
        $response->assertJson([]);
        $response->assertJsonMissing($debitCards);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $token = $this->user->token();

        $data = [
            'type' => 'Visa',
        ];
        $response = $this->withHeaders([
            'Authorization' => $token,
        ])->post('/api/debit-cards', $data);
        $response->assertStatus(201);

        $debitCard = new DebitCard();
        $debitCard->type = 'Visa';
        $debitCard->user_id = $this->user->id;
        $debitCard->number = rand(1000000000000000, 9999999999999999);
        $debitCard->expiration_date = Carbon::now()->addYear();

        $this->assertTrue($this->user->can('create', $debitCard));
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $token = $this->user->token();
        $data = [
            'user_id' => $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
            'type' => 'Visa'
        ];

        $debitCard = DebitCard::create($data);
        $response = $this->withHeaders([
            'Authorization' => $token,
        ])->get('/api/debit-cards/', ['debitCard' => $debitCard]);
        $response->assertStatus(200);

        $this->assertTrue($this->user->can('view', $debitCard));
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $anotherUser = User::factory()->create();
        $data = [
            'user_id' => $anotherUser->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
            'type' => 'Visa'
        ];

        $debitCard = DebitCard::create($data);
        $response = $this->withHeaders([
            'Authorization' => $this->user->token(),
        ])->get('/api/debit-cards/', ['debitCard' => $debitCard]);

        $response->assertStatus(200);
        $response->assertJsonMissing($debitCard->toArray());
        $this->assertTrue($this->user->cannot('view', $debitCard));
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $data = [
            'user_id' => $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
            'type' => 'Visa'
        ];
        $debitCard = DebitCard::create($data);
        $this->assertTrue($this->user->can('update', $debitCard));
        $this->json('put', url('api/debit-cards',$debitCard), ['is_active' => true])
            ->assertStatus(200);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $data = [
            'user_id' => $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
            'type' => 'Visa'
        ];
        $debitCard = DebitCard::create($data);
        $this->assertTrue($this->user->can('update', $debitCard));
        $this->json('put', url('api/debit-cards',$debitCard), ['is_active' => false])
            ->assertStatus(200);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $data = [
            'user_id' => $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
            'type' => 'Visa'
        ];
        $debitCard = DebitCard::create($data);
        $this->json('put', url('api/debit-cards',$debitCard), ['is_active' => "abc"])
            ->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $data = [
            'user_id' => $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
            'type' => 'Visa'
        ];
        $debitCard = DebitCard::create($data);
        $this->assertTrue($this->user->can('delete', $debitCard));

        $response = $this->withHeaders([
            'Authorization' => $this->user->token(),
        ])->delete('/api/debit-cards', ['debitCard' => $debitCard]);
        $response->assertStatus(204);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $data = [
            'user_id' => $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
            'type' => 'Visa'
        ];
        $debitCard = DebitCard::create($data);
        DebitCardTransaction::create([
            'debit_card_id' => $debitCard->id,
            'amount' => 5000,
            'currency_code' => 'VND',
        ]);
        $this->assertTrue($this->user->cannot('delete', $debitCard));
        $response = $this->withHeaders([
            'Authorization' => $this->user->token(),
        ])->delete('/api/debit-cards', ['debitCard' => $debitCard]);
        $response->assertStatus(200);
    }

    // Extra bonus for extra tests :)
}
