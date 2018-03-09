<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Mail;
class UsersController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth',[
            'except' => ['show','create','store','index','confirmEmail']
        ]);
        $this->middleware('guest',[
            'only' => ['create']
        ]);
    }

    public function index()
    {
        //$users = User::all();
        $users = User::paginate(10);
        return view('users.index',compact('users'));
    }

  	public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
    	return view('users.show',compact('user'));
    }

    public function store(Request $request)
    {
        //var_dump($request);exit;
    	$this->validate($request,[
    		'name'=>'required|max:50',
    		'email'=>'required|email|unique:users|max:255',
    		'password'=>'required|confirmed|min:6'

    	]);

    	$user = User::create([
    		'name'=>$request -> name,
    		'email'=>$request -> email,
    		'password'=>bcrypt($request -> password),
    	]);

        //Auth::login($user);
    	//session()->flash('success','欢迎，您将在这里开启一段新的旅程~');
        //	return redirect()->route('users.show',[$user]);
        $this->sendEmailConfirmationTo($user);
        session()->flash('success','验证邮件已发送到您的注册邮箱，请查收。');
        return redirect('/');
    }

    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $from = '1137796654@qq.com';
        $name = 'fan';
        $to = $user -> email;
        $subject = "感谢您注册 Sample 应用！请确认您的邮箱。";

        Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });

    }


    public function confirmEmail($token)
    {
        $user = User::where('activation_token',$token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success','恭喜您，激活成功！');
        return redirect()->route('users.show',[$user]);
    }

    public function edit(User $user)
    {
        $this->authorize('update',$user);
        return view('users.edit',compact('user'));
    }

    public function update(User $user,Request $request)
    {
        $this->validate($request,[
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6'
        ]);
        $this->authorize('update',$user);
        $data = [];
        $data['name'] = $request -> name;
        if($request -> password)
        {
            $data['password'] = bcrypt($request -> password);
        }
        /*$user->update([
            'name' => $request -> name,
            'password' => bcrypt($request -> password)
        ]);*/

        $user -> update($data);

        session()->flash('success','个人资料更新成功！');

        return redirect()->route('users.show',$user -> id);
    }


    public function destroy(User $user)
    {
        $this->authorize('destroy',$user);
        $user->delete();
 
        session()->flash('success','成功删除用户！');

        return back();
    }







}
