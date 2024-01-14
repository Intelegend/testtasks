<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadsModel;
use App\Models\ContactsModel;
use App\Models\TokenModel;
use Carbon\Carbon;

class NoteSender extends Controller
{
    const SECRET_KEY = 'hn1EsGON707Lfv6gsGTMjlwXoUVkgRtna6oBxRVVoQ3TlhG5AzVJOQSzL5UAlSMn';
    const INTEGRATION_ID = 'f20ce64a-8bfc-4281-812c-446ac8151ded';
    const REDIRECT_URI = 'https://f649-109-252-186-103.ngrok-free.app/token';

    public function handler(Request $request)
    {
        $requestBody = $request->collect();
        $note = [];
        $leadId = 0;
        $contactId = 0;
        $leadsModel = new LeadsModel;
        $contactsModel = new ContactsModel;
        $accessModel = new TokenModel;
        $accountId = (int)$requestBody['account']['id'];
        $uri = sprintf('https://%s.amocrm.ru/', $requestBody['account']['subdomain']);

        $requestBody->each(function ($data, $requestKey) use (&$note, $leadsModel, $contactsModel, &$leadId, &$contactId) {
            if($requestKey === "leads") {
                foreach ($data as $key => $value) {
                    if($key === "add") {
                        $note = array_map(function($noteData) use ($leadsModel, &$leadId) {
                            $dateCreate = date("Y-m-d H:i:s", $noteData['date_create']);
                            $leadsModel->lead_id = $noteData['id'];
                            $leadId = $noteData['id']; 
                            $leadsModel->name = isset($noteData['name']) ? $noteData['name'] : "тест";
                            $leadsModel->price = $noteData['price'];
                            $leadsModel->save();
                            return [
                                'lead_add' =>
                                [
                                    'name' => isset($noteData['name']) ? $noteData['name'] : "тест",
                                    'responsible_user_id' => $noteData['responsible_user_id'],
                                    'date_create' => $dateCreate
                                ]
                            ];
                            
                        }, $value);
                    }

                    if ($key === "update") {
                        $note = array_map(function($noteData) use ($leadsModel, &$leadId) {
                        $leadsCollection = LeadsModel::where('lead_id', $noteData['id'] )->get(['lead_id','price','name']);
                            if ($leadsCollection->isEmpty()) {
                                $createdAt = date("Y-m-d H:i:s", $noteData['created_at']);
                                $leadId = $noteData['id'];
                                $leadsModel->lead_id = $noteData['id'];
                                $leadsModel->name = isset($noteData['name']) ? $noteData['name'] : "тест";
                                $leadsModel->price = $noteData['price'];
                                $leadsModel->save();
                                return [
                                    'lead_add' =>
                                    [
                                        'name' => isset($noteData['name']) ? $noteData['name'] : "тест",
                                        'responsible_user_id' => $noteData['responsible_user_id'],
                                        'date_create' => $createdAt
                                    ]
                                ];
                            } else {

                            $leadsDiff = [];
                            $leadId = $noteData['id'];
                                foreach ($noteData as $itemKey => $item) {
                                    if(array_key_exists($itemKey, $leadsCollection->toArray()[0])) {
                                        if(!in_array($item, $leadsCollection->toArray()[0])) {
                                            $leadsDiff[$itemKey] = $item;
                                        }
                                    }
                                }

                            $lastModified = date("Y-m-d H:i:s", $noteData['last_modified']);
                            if(!empty($leadsDiff)) {
                                $leadsModel->where('lead_id', $noteData['id'])->update($leadsDiff);
                            }
                            return [
                                'lead_update' =>
                                    [
                                        'name' => isset($noteData['name']) ? $noteData['name'] : "",
                                        'changes' => $leadsDiff,
                                        'last_modified' => $lastModified,
                                        'date_create' => $noteData['date_create']
                                    ]
                                ];
                            }

                        }, $value);
                    }
                }
            }

            if($requestKey === "contacts") {
                foreach ($data as $key => $value) {
                    if($key === "add") {
                        $note = array_map(function($noteData) use ($contactsModel, &$contactId) {
                        $contactsModel->contact_id = $noteData['id'];
                        $contactsModel->name = isset($noteData['name']) ? $noteData['name'] : "тест";
                        $contactId = $noteData['id'];
                        foreach($noteData['custom_fields'] as $customFields) {
                            if($customFields['code'] === 'PHONE') {
                                if($customFields['values'][0]['value']) {
                                    $contactsModel->phone = $customFields['values'][0]['value'];
                                } 
                            }
                            if($customFields['code'] === 'EMAIL') {
                                if($customFields['values'][0]['value']) {
                                    $contactsModel->phone = $customFields['values'][0]['value'];
                                } 
                            }
                            if($customFields['code'] === 'POSITION') {
                                if($customFields['values'][0]['value']) {
                                    $contactsModel->phone = $customFields['values'][0]['value'];
                                } 
                            }
                        }
                        $contactsModel->save();
                        $dateCreate = date("Y-m-d H:i:s", $noteData['date_create']);
                        return [
                            'contact_add' =>
                            [
                                'name' => isset($noteData['name']) ? $noteData['name'] : "",
                                'responsible_user_id' => $noteData['responsible_user_id'],
                                'date_create' => $dateCreate
                            ]
                        ];
                            
                    }, $value);
                }

                    if($key === "update") {
                        $note = array_map(function($noteData) use ($contactsModel, &$contactId) {
                        $contactsCollection = ContactsModel::where('contact_id', $noteData['id'] )
                        ->get(['contact_id','name','phone','email','job']);
                    if($contactsCollection->isEmpty()) {
                        $contactId = $noteData['id'];
                        $contactsModel->contact_id = $noteData['id'];
                        $contactsModel->name = isset($noteData['name']) ? $noteData['name'] : "тест";
                        foreach($noteData['custom_fields'] as $customFields) {
                            if($customFields['code'] === 'PHONE') {
                                if($customFields['values'][0]['value']) {
                                    $contactsModel->phone = $customFields['values'][0]['value'];
                                } 
                            }
                            if($customFields['code'] === 'EMAIL') {
                                $contactsModel->email = $customFields['values'][0]['value'];
                            }
                            if($customFields['code'] === 'POSITION') {
                                $contactsModel->job = $customFields['values'][0]['value'];
                            }
                        }
                        $contactsModel->save();
                        $dateCreate = date("Y-m-d H:i:s", $noteData['date_create']);
                        return [
                            'contact_add' =>
                            [
                                'name' => isset($noteData['name']) ? $noteData['name'] : "",
                                'responsible_user_id' => $noteData['responsible_user_id'],
                                'date_create' => $dateCreate
                            ]
                        ];
                    } else {

                        $contactDiff = [];
                        $contactId = $noteData['id'];
                        foreach ($noteData as $itemKey => $item) {
                            if(array_key_exists($itemKey, $contactsCollection->toArray()[0])) {
                                if(!in_array($item, $contactsCollection->toArray()[0])) {
                                    $contactDiff[$itemKey] = $item;
                                }
                            }                            
                        }

                        foreach ($noteData['custom_fields'] as $customFields) {
                            if($customFields['code'] === 'PHONE') {
                                if(!in_array($customFields['values'][0]['value'], $contactsCollection->toArray()[0])) {
                                    $contactDiff['phone'] = $customFields['values'][0]['value'];
                                }
                            }
                            if($customFields['code'] === 'EMAIL') {
                                if(!in_array($customFields['values'][0]['value'], $contactsCollection->toArray()[0])) {
                                    $contactDiff['email'] = $customFields['values'][0]['value'];
                                }
                            }
                            if($customFields['code'] === 'POSITION') {
                                if(!in_array($customFields['values'][0]['value'], $contactsCollection->toArray()[0])) {
                                    $contactDiff['job'] = $customFields['values'][0]['value'];
                                }
                            }
                        }

                        $lastModified = date("Y-m-d H:i:s", $noteData['last_modified']);

                        if(!empty($contactDiff)) {
                            $contactsModel->where('contact_id', $noteData['id'])->update($contactDiff);
                        }
                        return [
                            'contact_update' =>
                            [
                                'name' => isset($noteData['name']) ? $noteData['name'] : "",
                                'changes' => $contactDiff,
                                'last_modified' => $lastModified,
                                'date_create' => $noteData['date_create']
                            ]
                        ];
                    }
     
                    }, $value);
                }
            }
        }
    });

    $now = Carbon::now();
    $expiresDate = Carbon::now();
    $expiresDate = $expiresDate->addDays(1);

    $accessTokenCollection = TokenModel::where('account_id', $accountId)->get();

    if($accessTokenCollection->isEmpty()) {

        $tokenData = [
            'client_id' => self::INTEGRATION_ID,
            'client_secret' => self::SECRET_KEY,
            'grant_type' => 'authorization_code',
            'code' => 'def50200dbbf4961bb6fb79878c33b3ad1df2e8b83c9056175bf9cc81ef28e942f748fb5fe695cae25f662840cd01cc8f4618b83b16e43474bf8ec8098f3eb37016be1739a155c4ce4c3b65168da1ac418d4290bc52d7f0238904ba3ce475155ed365ea55a3bf66f1488bc3afeb5da66bfe6894b8fde854c2d7df57026b4776e028b4d8c41c6c19e270f91255f21e3637a8b5ca14625683fa5a07253ccee467994dc724e985c8817791b4adcccf0b5c70a0e9944a88a7424af083fd4cfb796d6bb86ebe1df78270773951c04bf67c3b0beaf66e6c28811059194dc9f1773446b9b82e23ebd3e7b0f1d49e0927093f5663912c0274033dd9fec4877ad27e1c8dbfb3136d81bc4ebcf8afe65fb2a5354a910d1b8d5fe45f108967bb424696ad5716996102d4484e9ad044621e161828bece2f3adcb8a58c4e1c7c16b0e8f6a2638f2ebe744f27252fad0358592327a84ac3cab2820737203665d2684b599f7ace7d10c4016b9f8d4670409efc1bd1022affab9c62fe43033f284241140f76fea77cb1010b79805dc6001e93866dbd8f5f5a5faaa3d76baa57b25c48f2e9fd9f816f640263a13b4cd120f5edd9b0be354971814fa29cb52f55373eea9552be34630ff4a470d88337954d523dcf7585cff86f5753efedc4f805a09b4fdc029d88d52157f481f840b033879c2d22105b7a5120da8ed8bf8f07471eea3ce510bf522600caa',
            'redirect_uri' => self::REDIRECT_URI,
        ];

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $uri . 'oauth2/access_token');
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($tokenData));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        curl_close($curl);

        $tokenData = json_decode($out, true);

        $accessModel->access_token = $tokenData['access_token'];
        $accessModel->refresh_token = $tokenData['refresh_token'];
        $accessModel->expires_at = $expiresDate;
        $accessModel->account_id = $accountId;
        $accessModel->save();

    } else {
        
        $token = $accessTokenCollection->where('expires_at','<', $now);

        if($token->isEmpty()) {
            $entityType = empty($leadId) ? 'contacts' : 'leads';
            $entityId = empty($leadId) ? $contactId : $leadId;
            $noteRequestUrl = sprintf('%sapi/v4/%s/%s/notes', $uri, $entityType, $entityId);
            $out = print_r($note, true);
            $out = str_replace("Array", "", $out);
            $noteData = [
                [
                      "note_type" => "common", 
                      "params" => [
                         "text" => $out
                      ] 
                   ] 
               ];
            
            $authorization = sprintf("Authorization: Bearer %s", $accessTokenCollection->toArray()[0]['access_token'] );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch,CURLOPT_URL, $noteRequestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($noteData));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $out = curl_exec($ch);
            curl_close($ch);

        } else {
            $tokenData = $token->toArray()[0];
            $refreshData = [
                'client_id' => self::INTEGRATION_ID,
                'client_secret' => self::SECRET_KEY,
                "grant_type" => "refresh_token",
                "refresh_token" => $tokenData['refresh_token'],
                'redirect_uri' => self::REDIRECT_URI,
            ];

            $curl = curl_init();
            curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
            curl_setopt($curl,CURLOPT_URL, $uri . 'oauth2/access_token');
            curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
            curl_setopt($curl,CURLOPT_HEADER, false);
            curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($refreshData));
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
            $out = curl_exec($curl);
            curl_close($curl);

            $out = json_decode($out, true);

            $accessModel->where('account_id', $accountId)->update(
            [
                'access_token' => $out['access_token'],
                'refresh_token' => $out['refresh_token'],
                'expires_at' => $expiresDate
            ]);
        }
    }
        return 200;
    }
}
