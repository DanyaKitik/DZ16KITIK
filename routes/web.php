<?php

use App\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $url = 'https://www.facebook.com/dialog/oauth';

    $parameters = [
        'client_id' => env('OAUTH_FACEBOOK_ID'),
        'redirect_uri' => env('OAUTH_FACEBOOK_CALLBACK_URI'),
        'response_type' => 'code',
        'scope' => 'email'
    ];

    $url .= '?' . http_build_query($parameters);

    return  view('/login', ['url' => $url]);
});

Route::get('/callback' , function (){
    $response = Http::withHeaders([
        'Accept'=>'application/json'
    ])->post('https://graph.facebook.com/oauth/access_token' , [
        'client_id' => env('OAUTH_FACEBOOK_ID'),
        'redirect_uri' => env('OAUTH_FACEBOOK_CALLBACK_URI'),
        'client_secret' => env('OAUTH_FACEBOOK_SECRET'),
        'code' => \request()->get('code')
    ]);

    $params = array(
        'access_token' => $response['access_token'],
        'fields'       => 'id,email,first_name,last_name'
    );
    $response = Http::get('https://graph.facebook.com/me?' . urldecode(http_build_query($params)));

    $userInfo = json_decode($response->body());
    $user = User::where('email', $userInfo->email)->first();
    if ($user === null){
        $user = new User;
        $user->name = $userInfo->first_name. ' '. $userInfo->last_name;
        $user->email = $userInfo->email;
        $user->email_verified_at = now();
        $user->password = \Illuminate\Support\Facades\Hash::make(Str::random(100));
        $user->remember_token = Str::random(10);
        $user->save();
    };
    \Illuminate\Support\Facades\Auth::login($user);
    return redirect('/member');
});

Route::get('/member', function (){
   dd(request()->user());
});



