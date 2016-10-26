<?php

namespace Rznag\MultiLoginRestrictor\Middleware;

use Closure;
use Auth;
use Session;
use Config;
use Log;
use Rznag\MultiLoginRestrictor\Models\UserLogin;

class MultiLoginRestrictorMiddleware
{

    public function handle($request, Closure $next){

        if (Auth::guard(null)->guest()) return $next($request);

        $user = Auth::user();

        $numSeats = $user->num_seats;

        $numLogins = UserLogin::where('user_id', $user->id)->count();

        if ($numSeats < $numLogins)
        {
            // delete the earlier logins to bring the number back up to the allowed quota
            $logins = UserLogin::where('user_id', $user->id)->orderBy('login_time')->take($numLogins - $numSeats)->get();
            $loginIds = [];
            foreach ($logins as $login)
            {
                $loginIds[]= $login->id;
            }
            UserLogin::whereIn('id', $loginIds)->delete();
        }

        $userLoginTime = Session::get(Config::get('multi-login-restrictor.login_time_session_key'));

        // get the login associated with this user's session
        $userLogin = UserLogin::where('user_id', $user->id)->where('login_time', $userLoginTime)->first();

        // if it's missing then log him out
        if (!$userLogin)
        {
            Log::info('Logging out user due to maximum seat limit reached.  User id: ' . $user->id);
            Auth::logout();
            Session::flush();
            Session::save();


            if ($request->ajax())
            {
                \App::abort(403);
            }
            else
            {
                return redirect()->guest('login')->with('global', 'You was logged out because there was a login from another location.');
            }
        }
        return $next($request);
    }
}
