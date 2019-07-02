<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Models\Position;

use JWTAuth;
use JWTFactory;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
       $this->middleware('jwt.verify', ['except' => ['login', 'token', 'getUser']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function token()
    {
        $factory = JWTFactory::customClaims([
            'sub'   => env('API_ID'),
            
        ]);

        $payload = $factory->make();

        $token = JWTAuth::encode($payload);
        
        return response()->json([
            "success" => true,
            "token" => (string) $token
        ], 200);
       
    }

    /**
     * Registration new user
     * @param Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:60',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'required|regex:/^[\+]{0,1}380([0-9]{9})$/|min:10|unique:users',
            'position_id' => 'required|integer|min:1',
            'photo' => 'required|max:5120|mimes:jpeg,jpg|dimensions:min_width:70,min_height:70',
        ]);

        if($validator->fails())
        {
            $unique = $validator->failed();
  
            if (!empty($unique['email']['Unique']) && !empty($unique['phone']['Unique']))
            {
                return response()->json([
                    "success" => false,
                    "message" => "User with this phone or email already exist",
                ], 409);
            } else {

                return response()->json([
                    "success" => false,
                    "message" => "Validation failed",
                    "fails" => $validator->errors(),    
                ], 422);

                
            }
 
        }

        // Formation the file name and extension
        $fileName = request()->file('photo')->getClientOriginalName() . '.' . request()->file('photo')->getClientOriginalExtension();

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'phone' => $request->get('phone'),
            'position_id' => $request->get('position_id'),
            'photo' => $fileName,
        ]);

        $request->file('photo')->storeAs('images',$fileName);

        JWTAuth::parseToken()->invalidate();

        return response()->json([
            "success" => true,
            "user_id" => $user->id,
            "message" => "New user successfully registered"
        ], 200);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Pagination function
     * @param Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pagination(Request $request)
    {
        $count = 5;
        $page = 0;

        $validator = Validator::make($request->all(), [
            'count' => 'required|numeric|min:1|max:100',
            'page' => 'required|numeric|min:1',
        ]);

        if($validator->fails())
        {
            $customError = $validator->failed();
            dd($customError);

            if (!empty($customError['count']['Required']))
            {
                $count = $request->input('count');
            
                if (!empty($customError['page']['Required']))
                {
                    $page = $request->input('page');
                    if(!is_numeric($page) || ($page < 1))
                    {
                        return response()->json([
                            "success" => false,
                            "message" => "Validation failed",
                            "fails" => [
                                "count" => [
                                    "The count must be an integer."
                                ],
                                "page" => [
                                    "The page must be at least 1."
                                ]
                            ]
                        ], 422);
                    }
                }
            }
        } else {
            $count = $request->input('count');
            $page = $request->input('page');
        }

        $users = \App\User::paginate($count);

        if(empty($users))
        {
            return response()->json([
                "success" => false,
                "message" => "Page not found"
            ], 404);
        }

        $users->withPath('api/v1/users');

        $users->appends(['count' => '5'])->links();

        $users = $users->toArray();

        return response()->json([
            "success" => true,
            "page" => $page,
            "total_pages" => $users['last_page'],
            "total_users" => $users['total'],
            "count" => $users['per_page'],
            "links" => [
                "next_url" => $users['next_page_url'],
                "prev_url" => $users['prev_page_url'],
            ],
            "users" => $users['data']
        ], 200);
    }

    /**
     * API Login, on success return JWT Auth token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('name', 'password');
        
        $rules = [
            'name' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($credentials, $rules);
        if($validator->fails()) {
            return response()->json(['success'=> false, 'error'=> $validator->messages()], 401);
        }
        
        $credentials['is_verified'] = 1;
        
        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['success' => false, 'error' => 'We cant find an account with this credentials. Please make sure you entered the right information and you have verified your email address.'], 404);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['success' => false, 'error' => 'Failed to login, please try again.'], 500);
        }

        $user = User::where('name', $request['name'])->first();
        CashbackIp::create([
                'date' => time(),
                'user_id' =>$user->id,
                'ip' => $request->ip(),
        ]);
        // all good so return the token
        return response()->json(['success' => true, 'data'=> [ 'token' => $token ]], 200);
    }

    /**
     * API Get informations about user 
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser($id)
    {
        if(!empty($id) && !is_numeric($id))
        {
            return response()->json([
                "success" => false,
                "message" => "Validation failed",
                "fails" => [
                    "user_id" => [
                        "The user_id must be an integer."
                    ]
                ]
            ], 400);
        }

        $user = User::find($id);

        if(!empty($user)){
            
            return response()->json([
                "success" => true,
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "phone" => $user->phone,
                    "position" => $user->position->name,
                    "position_id" => $user->position_id,
                    "photo" => $user->photo
                ]
            ], 200);

        } else {
            return response()->json([
                "success" => false,
                "message" => "The user with the requested identifier does not exist",
                "fails" => [
                    "user_id" => [
                        "User not found"
                    ]
                ]
            ], 404);
        }

        return response()->json(['success'=> false, 'error'=> "Verification code is invalid."]);

    }
}