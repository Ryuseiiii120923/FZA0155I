<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\Employee as AuthEmployee;
use App\Models\Auth\Worker as AuthWorker;
use App\Models\Employee;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('components.auth.login');
    }

    public function login(Request $request)
    {
        // --- QR Login ---
        if ($request->has('qr')) {
            $qrData = $request->input('qr');

            if (is_string($qrData)) {
                $qrData = json_decode($qrData, true);
            }

            if (isset($qrData['userid'], $qrData['password'])) {

                // GL role
                if ($qrData['role'] === 'GL') {
                    $user = AuthEmployee::where('社員CD', $qrData['userid'])->first();

                    if ($user && $user->PASSWORD === $qrData['password']) {
                        Auth::login($user);
                        $request->session()->regenerate();
                        return redirect()->route('dashboard');
                    }

                    return back()->withErrors(['credentials' => 'Invalid QR code login']);
                }

                // Worker role
                $user = AuthWorker::where('作業員CD', $qrData['userid'])->first();

                if (!$user) {
                    return back()->withErrors(['credentials' => 'User not found']);
                }

                $pass = (int)$user->社員CD . $user->RECNO;

                if ((string)$pass === $qrData['password']) {
                    Log::info('QR password matched', ['user_id' => $user->作業員CD]);

                    try {
                        Auth::guard('worker')->login($user);
                        $request->session()->regenerate();
                        session(['process' => $qrData['process']]);

                        Log::info('Worker logged in', ['user_id' => $user->作業員CD]);

                        return redirect()->route('prencode');
                    } catch (\Throwable $e) {
                        Log::error('Worker login failed', ['message' => $e->getMessage()]);
                        return back()->withErrors(['credentials' => 'Login error: ' . $e->getMessage()]);
                    }
                }

                return back()->withErrors(['credentials' => 'Invalid QR code login']);
            }
        }

        // --- Manual Login ---
        $request->validate([
            'userid'   => 'required|integer',
            'password' => 'required|string',
        ]);

        $user = AuthEmployee::where('社員CD', $request->input('userid'))->first();

        if ($user && $user->PASSWORD === $request->input('password')) {
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->route('landing-page');
        }

        return back()->withErrors(['credentials' => 'Incorrect credentials']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        Auth::guard('worker')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}