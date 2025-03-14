<?php

namespace Tests\Unit;

use App\Http\Controllers\API\AuthController;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected $authController;

    public function setUp(): void
    {
        parent::setUp();
        $this->authController = new AuthController();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_signup_creates_user_and_returns_token_with_valid_data()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $validator = Mockery::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('fails')->once()->andReturn(false);
        Validator::shouldReceive('make')->once()->with([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ], [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ])->andReturn($validator);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('createToken')->once()->with('API TOKEN')->andReturn((object) ['plainTextToken' => 'mock-token']);
        $user->id = 1;
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->role_id = RoleEnum::CLIENT;

        $userClass = Mockery::mock('alias:App\Models\User');
        $userClass->shouldReceive('create')->once()->with([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Mockery::on(function ($hashedPassword) {
                return Hash::check('password123', $hashedPassword);
            }),
            'role_id' => RoleEnum::CLIENT,
        ])->andReturn($user);

        $response = $this->authController->signup($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([
            'user' => $user,
            'token' => 'mock-token',
        ], $response->getData(true));
    }

    public function test_signup_returns_validation_error_with_invalid_data()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn([
            'name' => '',
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $validator = Mockery::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('errors')->once()->andReturn(collect([
            'name' => ['The name field is required.'],
            'email' => ['The email must be a valid email address.'],
            'password' => ['The password field is required.'],
        ]));
        Validator::shouldReceive('make')->once()->with([
            'name' => '',
            'email' => 'invalid-email',
            'password' => '',
        ], [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ])->andReturn($validator);

        $response = $this->authController->signup($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals([
            'status' => false,
            'message' => 'validation error',
            'errors' => [
                'name' => ['The name field is required.'],
                'email' => ['The email must be a valid email address.'],
                'password' => ['The password field is required.'],
            ],
        ], $response->getData(true));
    }

    public function test_login_authenticates_user_and_returns_token_with_valid_credentials()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);
        $request->shouldReceive('only')->with(['email', 'password'])->once()->andReturn([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $validator = Mockery::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('fails')->once()->andReturn(false);
        Validator::shouldReceive('make')->once()->with([
            'email' => 'john@example.com',
            'password' => 'password123',
        ], [
            'email' => 'required|email',
            'password' => 'required',
        ])->andReturn($validator);

        Auth::shouldReceive('attempt')->once()->with([
            'email' => 'john@example.com',
            'password' => 'password123',
        ])->andReturn(true);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('createToken')->once()->with('API TOKEN')->andReturn((object) ['plainTextToken' => 'mock-token']);
        $user->id = 1;
        $user->email = 'john@example.com';

        $query = Mockery::mock('stdClass');
        $query->shouldReceive('first')->once()->andReturn($user);
        $userClass = Mockery::mock('alias:App\Models\User');
        $userClass->shouldReceive('where')->once()->with('email', 'john@example.com')->andReturn($query);

        $response = $this->authController->login($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'user' => $user,
            'token' => 'mock-token',
        ], $response->getData(true));
    }

    public function test_login_returns_error_with_invalid_credentials()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn([
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);
        $request->shouldReceive('only')->with(['email', 'password'])->once()->andReturn([
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $validator = Mockery::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('fails')->once()->andReturn(false);
        Validator::shouldReceive('make')->once()->with([
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ], [
            'email' => 'required|email',
            'password' => 'required',
        ])->andReturn($validator);

        Auth::shouldReceive('attempt')->once()->with([
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ])->andReturn(false);

        $response = $this->authController->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals([
            'status' => false,
            'message' => 'Email or Password does not match with our record.',
        ], $response->getData(true));
    }

    public function test_login_returns_validation_error_with_missing_fields()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn([
            'email' => '',
            'password' => '',
        ]);

        $validator = Mockery::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('errors')->once()->andReturn(collect([
            'email' => ['The email field is required.'],
            'password' => ['The password field is required.'],
        ]));
        Validator::shouldReceive('make')->once()->with([
            'email' => '',
            'password' => '',
        ], [
            'email' => 'required|email',
            'password' => 'required',
        ])->andReturn($validator);

        $response = $this->authController->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals([
            'status' => false,
            'message' => 'validation error',
            'errors' => [
                'email' => ['The email field is required.'],
                'password' => ['The password field is required.'],
            ],
        ], $response->getData(true));
    }
}