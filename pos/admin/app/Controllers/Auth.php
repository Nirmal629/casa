<?php

namespace App\Controllers;
use App\Models\ClubModel;

class Auth extends BaseController
{
    public function index()
    {
        helper(['form']);
        echo view('admin/common/login');
    } 

    public function login()
    {
        $session = session();
        $model = new ClubModel();
        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');
        $data = $model->where('email', $email)->first();
        if($data){
            $pass = $data['password'];
            $verify_pass = password_verify($password, $pass);
            if($verify_pass){
                $ses_data = [
                    'user_id'       => $data['id'],
                    'user_name'     => $data['owner_name'],
                    'club_name'     => $data['club_name'],
                    'user_email'    => $data['email'],
                    'logged_in'     => TRUE
                ];
                $session->set($ses_data);
                // return redirect()->to('/club-owner/dashboard');
                return redirect()->to('/club-owner/list-membership');
            }else{
                $session->setFlashdata('msg', 'Wrong Password');
                return redirect()->to('/login');
            }
        }else{
            $session->setFlashdata('msg', 'Email not Found');
            return redirect()->to('/login');
        }
    }

    public function logout()
    {
        $session = session();
        $session->destroy();
        return redirect()->to('/login');
    }
}

