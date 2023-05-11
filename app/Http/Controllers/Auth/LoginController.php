<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\CommonController;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\TbCmnUser;
use App\Models\TbLoginTrack;
use App\Models\TbChangePasswordHistory;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        try {
            $data = $request->all();
            $uiUsername = $data['User_Id'];
            $uiPassword = $data['User_Password'];

            if (empty($uiUsername) || empty($uiPassword)) {
                $request->session()->flash('message', 'Please enter username and password!');
                return redirect('/');
            } else {
                $user = TbCmnUser::where(['Record_Active_Flag' => 1, 'User_Id' => $uiUsername])->get()->first();
                $ipCount = TbLoginTrack::where(['Application_User_Ip_Address' => $request->ip()])->get()->sortByDesc('Pkid')->first();
                $passwordChangeHistory = TbChangePasswordHistory::where(['Record_Active_Flag' => 1, 'Username' => $uiUsername])->get()->first();
                $Count_Attempt_From_Ip = 0;
                if (empty($ipCount)) {
                    $Count_Attempt_From_Ip = $Count_Attempt_From_Ip + 1;
                } else {
                    $Count_Attempt_From_Ip = (int)$ipCount->Count_Attempt_From_Ip + 1;
                }
                if (($Count_Attempt_From_Ip >= 5)) {
                    $request->session()->flash('message', 'Your IP has been blocked! Contact administrator');
                    return redirect('/');
                } else {
                    if (!empty($user)) {
                        //check if there is active change password history
                        if (!empty($passwordChangeHistory)) {
                            if ($passwordChangeHistory->Username == $uiUsername  && $passwordChangeHistory->NewPassword ==  hash('sha3-512', $uiPassword)) {
                                return view('pages.user-pages.reset-password', compact('passwordChangeHistory'))->render();
                            } else {
                                $request->session()->flash('message', 'Invalid username or password!');
                                return redirect('/');
                            }
                        }
                        // normal authenticate section
                        if ($user->User_Id == $uiUsername  && $user->User_Password ==  hash('sha3-512', $uiPassword)) {
                            $request->session()->put('username', $user->User_Id);
                            $request->session()->put('id', $user->Pkid);
                            $request->session()->put('CLIENT_IP', $request->ip());
                            $request->session()->put('USER_TYPE', $user->User_Type);
                            $addLoginTrack = new TbLoginTrack();
                            $addLoginTrack->Record_Active_Flag_User = '1';
                            $addLoginTrack->Record_Active_Flag_Ip = '1';
                            $addLoginTrack->Count_Attempt_From_Ip = '0';
                            $addLoginTrack->Count_Attempt_From_User = '0';
                            $addLoginTrack->UserId = $request->session()->get('id');
                            // $result = (new CommonController)->insertDefaultColumns($request, $addLoginTrack);
                            $addLoginTrack->Application_User_Ip_Address = $request->session()->get('CLIENT_IP');
                            $addLoginTrack->Record_Insert_Date = new \DateTime();
                            $addLoginTrack->save();
                            return redirect('dashboard');
                        } else {
                            //wrong password
                            $userId = TbLoginTrack::where(['UserId' => $user->Pkid])->get()->sortByDesc('Pkid')->first();

                            $Count_Attempt_From_User = 0;
                            if (empty($userId)) {
                                $Count_Attempt_From_User = $Count_Attempt_From_User + 1;
                            } else {
                                $Count_Attempt_From_User = (int)$userId->Count_Attempt_From_User + 1;
                            }
                            $request->session()->flash('message', 'Invalid username or password!');
                            if (($Count_Attempt_From_User > 3) or ($Count_Attempt_From_Ip > 3)) {
                                $request->session()->flash('message', 'Multiple wrong Username or Password attempt! One attempt left');
                                //return redirect('/');
                            }
                            if (($Count_Attempt_From_User >= 5) or ($Count_Attempt_From_Ip >= 5)) {
                                $request->session()->flash('message', 'Maximum Attempt time excedeed! Contact administrator');
                                return redirect('/');
                            }
                            $Count_Attempt_From_User = $Count_Attempt_From_User;
                            $addLoginTrack = new TbLoginTrack();
                            $addLoginTrack->Record_Active_Flag_User = '0';
                            $addLoginTrack->Record_Active_Flag_Ip = '0';
                            $addLoginTrack->Count_Attempt_From_Ip = $Count_Attempt_From_Ip;
                            $addLoginTrack->Count_Attempt_From_User = $Count_Attempt_From_User;
                            $addLoginTrack->UserId = $user->Pkid;
                            $addLoginTrack->Application_User_Ip_Address = $request->ip();
                            $addLoginTrack->Record_Insert_Date = new \DateTime();
                            $addLoginTrack->save();
                            return redirect('/');
                        }
                    } else {
                        //wrong user id
                        $userId = TbLoginTrack::where(['Application_User_Ip_Address' => $request->ip()])->get()->sortByDesc('Pkid')->first();
                        $Count_Attempt_From_Ip = 0;
                        if (empty($userId)) {
                            $Count_Attempt_From_Ip = $Count_Attempt_From_Ip + 1;
                        } else {
                            $Count_Attempt_From_Ip = (int)$userId->Count_Attempt_From_Ip + 1;
                        }
                        $request->session()->flash('message', 'Invalid username or password!');
                        if ($Count_Attempt_From_Ip > 3) {
                            $request->session()->flash('message', 'Multiple wrong Username or Password attempt! One attempt left');
                            // return redirect('/');
                        }
                        if ($Count_Attempt_From_Ip >= 5) {
                            $request->session()->flash('message', 'Maximum Attempt time excedeed! Contact administrator');
                            return redirect('/');
                        }
                        $addLoginTrack = new TbLoginTrack();
                        $addLoginTrack->Record_Active_Flag_User = '0';
                        $addLoginTrack->Record_Active_Flag_Ip = '0';
                        $addLoginTrack->Count_Attempt_From_Ip = $Count_Attempt_From_Ip;
                        $addLoginTrack->Application_User_Ip_Address = $request->ip();
                        $addLoginTrack->Record_Insert_Date = new \DateTime();
                        $addLoginTrack->save();

                        return redirect('/');
                    }
                }
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
            //return redirect('/');
        }
    }

    public function logOut(Request $request)
    {        
        $request->session()->forget('username');
        $request->session()->forget('lastActivityTime');
        $request->session()->forget('id');
        $request->session()->flush();
     //   return view('pages.user-pages.login')->render();
       // return view('pages.user-pages.login')->render();
        return redirect('/logout');
    }
    public function forgotPassword(Request $request)
    {
        return view('pages.user-pages.forgot-password')->render();
    }
    public function findUser(Request $request)
    {
        try {
            $data = $request->all();
            $uiUsername = $data['User_Id'];
            if (empty($uiUsername)) {
                $request->session()->flash('message', 'Please enter username !');
                return redirect('forgot-password');
            } else {
                $user = TbCmnUser::where(['Record_Active_Flag' => 1, 'User_Id' => $uiUsername])->get()->first();
                if (!empty($user)) {
                    return view('pages.user-pages.find-user', compact('user'))->render();
                } else {
                    //wrong user id
                    $request->session()->flash('message', 'Invalid username !');
                    return redirect('forgot-password');
                }
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
            //return redirect('/');
        }
    }
    public function forgotUseraname(Request $request)
    {
        return view('pages.user-pages.forgot-username')->render();
    }
    public function findEmail(Request $request)
    {
        try {
            $data = $request->all();
            $uiUsername = $data['userId'];
            $uiEmailId = $data['Email_Id'];
            $user = TbCmnUser::where(['Record_Active_Flag' => 1, 'Pkid' => $uiUsername])->get()->first();
            if (empty($uiEmailId)) {
                $request->session()->flash('message', 'Please enter Email !');
                return view('pages.user-pages.find-user', compact('user'))->render();
            } else {
                $userWithmail = TbCmnUser::where(['Record_Active_Flag' => 1, 'User_Email' => $uiEmailId, 'Pkid' => $uiUsername])->get()->first();
                if (!empty($userWithmail)) {
                    $sub = 'Subsidy-Forgot Password';
                    $email = $userWithmail->User_Email; //getting email id from UI
                    $defaultPass = chr(random_int(97, 122)) . random_int(0, 9) . chr(random_int(97, 122)) . random_int(10, 99) . chr(random_int(97, 122)) . random_int(10, 99); //default password generated
                    //$transport=  \Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')->setUsername('santikumar.singh.cobigent@gmail.com')->setPassword('santikumar@cobigent');
                    $body = view('pages.user-pages.default-password', compact('defaultPass', 'user'))->render(); //page to be fixed on mail
                    // aws mail sending configuration
                    $transport = (new \Swift_SmtpTransport('email-smtp.us-east-1.amazonaws.com', 587, 'tls'))
                        ->setUsername('AKIAIBEAAPVQEXHXC52Q')
                        ->setPassword('An/yaA8MletLfoPLh2WumRsmDrFb/wlR05zSLKHMOnhQ')->setStreamOptions(array('ssl' => array('allow_self_signed' => true, 'verify_peer' => false)));

                    // Create the Mailer using your created Transport
                    $mailer = new \Swift_Mailer($transport);
                    // Create a message
                    $message = (new \Swift_Message($sub))
                        ->setFrom('no-reply@nedfi.com', 'NEDFi')
                        ->setTo($email)
                        ->setBody($body, 'text/html');

                    // Send the message
                    $send = $mailer->send($message);
                    if ($send) {
                        $result = (new CommonController)->sendDafaultPass($defaultPass, $user);
                        return view('pages.user-pages.password-recovery')->render();
                    } else {
                        $request->session()->flash('message', 'Failed to send Email !');
                        return view('pages.user-pages.find-user', compact('user'))->render();
                    }
                } else {
                    //wrong user id
                    $request->session()->flash('message', 'Invalid Email !');
                    return view('pages.user-pages.find-user', compact('user'))->render();
                }
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
            //return redirect('/');
        }
    }
    public function finduserEmail(Request $request)
    {
        try {
            $data = $request->all();
            $uiEmailId = $data['Email_Id'];
            if (empty($uiEmailId)) {
                $request->session()->flash('message', 'Please enter Email !');
                return redirect('forgot-username');
            } else {
                $user = TbCmnUser::where(['Record_Active_Flag' => 1, 'User_Email' => $uiEmailId])->get()->first();
                if (!empty($user)) {
                    $sub = 'Subsidy-Forgot Username';
                    $email = $user->User_Email; //getting email id from UI
                    $username =  $user->User_Id;
                    //$transport=  \Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')->setUsername('santikumar.singh.cobigent@gmail.com')->setPassword('santikumar@cobigent');
                    $body = view('pages.user-pages.user-name-recovery', compact('username', 'user'))->render(); //page to be fixed on mail
                    // aws mail sending configuration
                    $transport = (new \Swift_SmtpTransport('email-smtp.us-east-1.amazonaws.com', 587, 'tls'))
                        ->setUsername('AKIAIBEAAPVQEXHXC52Q')
                        ->setPassword('An/yaA8MletLfoPLh2WumRsmDrFb/wlR05zSLKHMOnhQ')->setStreamOptions(array('ssl' => array('allow_self_signed' => true, 'verify_peer' => false)));

                    // Create the Mailer using your created Transport
                    $mailer = new \Swift_Mailer($transport);
                    // Create a message
                    $message = (new \Swift_Message($sub))
                        ->setFrom('no-reply@nedfi.com', 'NEDFi')
                        ->setTo($email)
                        ->setBody($body, 'text/html');

                    // Send the message
                    $result = $mailer->send($message);
                    if ($result) {
                        //$result = (new CommonController)->sendDafaultPass($defaultPass,$userWithmail);
                        return view('pages.user-pages.username-recovery')->render();
                    } else {
                        $request->session()->flash('message', 'Failed to send Email !');
                        return view('pages.user-pages.forgot-username')->render();
                    }
                } else {
                    //wrong user id
                    $request->session()->flash('message', 'Invalid Email !');
                    return redirect('forgot-username');
                }
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
            //return redirect('/');
        }
    }
}
