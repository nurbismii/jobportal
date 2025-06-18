<?

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    public function verify(EmailVerificationRequest $request)
    {
        Log::info('🔒 Masuk ke EmailVerificationController@verify');

        $user = $request->user();

        if (!$user->hasVerifiedEmail()) {
            Log::info('Email diverifikasi oleh: ' . $user->email);
            $user->markEmailAsVerified();

            $user->status_akun = 1;
            $user->save();

            event(new Verified($user));
        }

        return redirect()->route('login')->with('status', 'Email berhasil diverifikasi!');
    }
}
