<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Store;
use Auth;
use Session;
use Illuminate\Support\Facades\Request;

class SessionExpired
{
    protected $session;
    protected $timeout = 1800;

    public function __construct(Store $session)
    {
        $this->session = $session;
    }
    public function handle($request, Closure $next)
    {
        // echo $flag; exit;
        $isLoggedIn = $request->path();
        $flag = 0;
        if (!$request->session()->has('username')) {

            if (!($isLoggedIn == '/' or $isLoggedIn == 'session-expire' or $isLoggedIn == 'logout' or $isLoggedIn == 'login' or $isLoggedIn == 'forgot-password' or $isLoggedIn == 'forgot-username' or $isLoggedIn == 'find-username' or $isLoggedIn == 'find-email' or $isLoggedIn == 'find-user-email' or $isLoggedIn == 'reset-password' or $isLoggedIn == 'reset-password-on-first-login' or $isLoggedIn == 'signout')) {
                if (Request::ajax()) {
                    $html_view = view('pages.user-pages.session-expire')->render();
                    return response()->json(["status" => "sessionExpired", "body" => $html_view]);
                } else {
                    return redirect('/session-expire');
                }
            }
            
        }
        if (!session('lastActivityTime')) {
            $this->session->put('lastActivityTime', time());
        } elseif (time() - $this->session->get('lastActivityTime') > $this->timeout) {
            //session expire will not apply on this routes
            if ($isLoggedIn == '/') {
                $flag = 1;
            } elseif ($isLoggedIn == 'session-expire') {
                $flag = 1;
            } elseif ($isLoggedIn == 'logout') {
                $flag = 1;
            } elseif ($isLoggedIn == 'login') {
                $flag = 1;
            } elseif ($isLoggedIn == 'signout') {
                $flag = 1;
            }

            // $flag == 0 means session has expired
            if ($flag == 0) {
                $cookie = cookie('intend', $isLoggedIn ? url()->current() : 'dashboard');
                $request->session()->forget('username');
                $request->session()->forget('lastActivityTime');
                $request->session()->forget('id');
                $request->session()->flush();
                if (Request::ajax()) {
                    $html_view = view('pages.user-pages.session-expire')->render();
                    return response()->json(["status" => "sessionExpired", "body" => $html_view]);
                }
                return redirect('/session-expire');
            } else {
                $isLoggedIn ? $this->session->put('lastActivityTime', time()) : $this->session->forget('lastActivityTime');
                return $next($request);
            }
        }

        $isLoggedIn ? $this->session->put('lastActivityTime', time()) : $this->session->forget('lastActivityTime');
        return $next($request);
    }
}
