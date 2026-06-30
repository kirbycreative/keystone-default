<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Keystone\Toolkit\Forms\Form;

class LoginController extends AdminController
{
    public function create(): View
    {
        return view('auth.login', [
            'loginForm' => $this->loginForm(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function loginForm(): Form
    {
        return (new Form())
            ->setAction(route('login.store'))
            ->setAttributes(['class' => 'margin:top:2 flex:column gap:1'])
            ->setSubmit('Log in', [
                'class' => 'btn btn--primary w:100',
            ])
            ->setSchema([
                'form' => [
                    'email' => [
                        'type' => 'email',
                        'label' => 'Email',
                        'attributes' => ['required' => true, 'autofocus' => true],
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => 'Password',
                        'attributes' => ['required' => true],
                    ],
                    'remember' => [
                        'type' => 'checkbox',
                        'label' => 'Remember me',
                        'checked' => old('remember') === '1',
                    ],
                ],
            ]);
    }
}
