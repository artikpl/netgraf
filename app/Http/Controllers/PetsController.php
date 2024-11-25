<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiResourceNotFoundException;
use App\Exceptions\InvalidApiResponseCodeException;
use App\Exceptions\UnknownStatusCodeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\InputBag;

class PetsController extends Controller
{

    private string $apiDomain = 'petstore.swagger.io';
    private string $apiPath = '/v2';

    private function apiQuery(string $path, string $method='GET',array $query=[],string $payload='') : array|\stdClass{

        $query = count($query) > 0 ? '?'.http_build_query($query,'',null) : '';
        $curl = curl_init();

        $options = [
            CURLOPT_URL => "https://{$this->apiDomain}{$this->apiPath}{$path}{$query}",
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => 'true',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ];
        if(strlen($payload)>0){
            $options[CURLOPT_HTTPHEADER][] = 'Content-type: application/json';
            $options[CURLOPT_POSTFIELDS] = $payload;
        }

        curl_setopt_array($curl,$options);
        $res = curl_exec($curl);
        $info = curl_getinfo($curl);
        if($info['http_code'] === 404){
            throw new ApiResourceNotFoundException();
        }
        if($info['http_code'] != 200){
            throw new InvalidApiResponseCodeException();
        }
        $data = json_decode($res);
        return $data;
    }

    private function normalizePet(\stdClass $pet) : \stdClass
    {
        return (object)[
            'id' => $pet->id,
            'name' => $pet->name ?? null,
            'category' => ($pet->category->id ?? 0 ) > 0 ? (object)[
                'id' => $pet->category->id,
                'name' => $pet->category->name ?? null
            ] : null,
            'photoUrls' => count($pet->photoUrls ?? [])>0 ? $pet->photoUrls : null,
            'tags' => count($pet->tags ?? [])>0 ? $pet->tags : null,
            'status' => $this->getStatusByCode($pet->status),
        ];
    }
    private function getPetsList(string $status) : array{
        $res = $this->apiQuery(path:'/pet/findByStatus',query:[
            'status' =>  $status
        ]);
        $pets = array_map([$this,'normalizePet'],$res);
        return $pets;
    }

    private function getStatusByCode(string $code) : \stdClass
    {
        $statuses = $this->getStatusesList();
        if(!isset($statuses[$code])){
            throw new UnknownStatusCodeException();
        }
        return $statuses[$code];

    }
    private function getStatusesList() : array{
        $statuses = [
            'available' => 'Dostępne',
            'pending' => 'Trwające',
            'sold' => 'Sprzedane'
        ];
        foreach($statuses as $status => $name){
            $statuses[$status] = (object)[
                'name' => $name,
                'code' => $status
            ];
        }
        return $statuses;
    }

    private function getErrorByCode(?string $code=null) : ?string
{
if(!isset($code)){
return null;
}
$errors = [
    'petnotfound' => 'Nie znaleziono zwierzaka'
];
return $errors[$code] ?? "Nieznany błąd";
}

    private function getConfirmationByCode(?string $code=null) : ?string
    {
        if(!isset($code)){
            return null;
        }
        $errors = [
            'petdeleted' => 'Usunięto zwierzaka'
        ];
        return $errors[$code] ?? null;
    }

    public function delete(Request $request,string $id)
    {
        try {
            $pet = $this->apiQuery('/pet/' . $id);
        } catch (ApiResourceNotFoundException $e) {
            return new JsonResponse([
                'error' => $this->getErrorByCode('petnotfound')
            ], 404);
        }
        $request->session()->put('success_code','petdeleted');
        $this->apiQuery(path:'/pet/'.$id,method:'DELETE');
        return new JsonResponse([
            'deleted' => [
                'id' => $pet->id
            ]
        ]);
    }
    public function create(Request $request) : RedirectResponse|JsonResponse
    {
        return $this->update(request:$request,id:'0');
    }

    public function update(Request $request,string $id) : RedirectResponse|JsonResponse
    {
        if($id !== '0') {
            try {
                $pet = $this->apiQuery('/pet/' . $id);
            } catch (ApiResourceNotFoundException $e) {
                return new JsonResponse([
                    'error' => $this->getErrorByCode('petnotfound')
                ], 404);
            }
        }
        $payload = $this->createApiPayload($request->getPayload());
        if($id === '0'){
            $res = $this->apiQuery(path:'/pet',method:'post', payload:json_encode($payload));
        }else{
            $payload->id = $id;
            $res = $this->apiQuery(path:'/pet',method:'put', payload:json_encode($payload));
        }
        return new JsonResponse([
            'pet' => $res,
            'url' => route('pets.details',['id' => $res->id])
        ]);
    }

    private function createApiPayload(InputBag $bag) : \stdClass
    {
        $inputs = $bag->all();
        $petName = $inputs['name'] ?? null;
        if(gettype($petName) !== 'string' || strlen(trim($petName))===0){
            throw new \Exception("Podaj imię zwierzaka");
        }
        $status = $inputs['status']['code'] ?? null;
        if(!is_string($status)){
            throw new \Exception("Błędny format statusu");
        }
        try {
            $status = $this->getStatusByCode($status);
        }catch(UnknownStatusCodeException $e){
            throw new \Exception("Status nie został znaleziony");
        }
        if(isset($inputs['category'])){
            $id = $inputs['category']['id'];
            if(!is_int($id) || $id<1){
                throw new \Exception("Błędne id kategorii");
            }
            $name = $inputs['category']['name'] ?? '';
            if(!is_string($name) || strlen(trim($name))===0){
                throw new \Exception("Pusta nazwa kategorii");
            }
            $inputs['category']['name'] = trim($name);
        }

        if(isset($inputs['tags'])){
            if(!is_array($inputs['tags'])){
                throw new \Exception("Węzeł z tagami nie jest tablicą!");
            }
            foreach($inputs['tags'] as $pos => $tag){
                $id = $tag['id'];
                if(!is_int($id) || $id<1){
                    throw new \Exception("Błędne id taga");
                }
                $name = $tag['name'] ?? '';
                if(!is_string($name) || strlen(trim($name))===0){
                    throw new \Exception("Pusta nazwa taga");
                }
                $inputs['tags'][$pos] = [
                    'id' => $id,
                    'name' => trim($name)
                ];
            }
        }

        if(isset($inputs['photoUrls'])){
            if(!is_array($inputs['photoUrls'])){
                throw new \Exception("Węzeł ze zdjęciami nie jest tablicą!");
            }
            foreach($inputs['photoUrls'] as $pos => $url){
                if(!is_string($url) || strlen(trim($url))===0){
                    throw new \Exception("Url zdjęcia jest puste");
                }
                $inputs['photoUrls'][$pos] = $url;
            }
        }
        return (object)[
            'name' => $petName,
            'category' => $inputs['category'] ?? null,
            'tags' => $inputs['tags'] ?? null,
            'photoUrls' => $inputs['photoUrls'] ?? null,
            'status' => $status->code
        ];
    }
    function list(Request $request): View|RedirectResponse
    {
        $statuses = $this->getStatusesList();
        $status = $request->get('status');
        if(!is_string($status) || !isset($statuses[$status])){
            return redirect()->route('pets.list',[
                'status' => array_key_first($statuses)
            ]);
        }

        $session = $request->session();
        $confirmation = $error = null;
        if($session->has('error_code')){
            $error = $this->getErrorByCode($session->get('error_code'));
            $session->remove('error_code');
        }
        if($session->has('success_code')){
            $confirmation = $this->getConfirmationByCode($session->get('success_code'));
            $session->remove('success_code');
        }
        $statuses[$status]->active = true;
        $pets = $this->getPetsList($status);

        return view('pets.list',[
            'error' => $error,
            'confirmation' => $confirmation,
            'statuses' => $statuses,
            'pets' => $pets
        ]);
    }

    function details(Request $request,string $id): View|RedirectResponse
    {
        if(strlen($id)>0) {
            try {
                $pet = $this->apiQuery('/pet/' . $id);
            }catch(ApiResourceNotFoundException $e){
                $request->session()->put('error_code','petnotfound');
                return redirect()->route('pets.list');
            }
            $pet = $this->normalizePet($pet);
        }
        $statuses = $this->getStatusesList();
        return view('pets.details',[
            'statuses' => $statuses,
            'pet' => $pet ?? null
        ]);
    }
    function empty(Request $request): View
    {
        return $this->details(request:$request,id:'');
    }
}
