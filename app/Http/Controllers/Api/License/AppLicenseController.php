<?php

namespace App\Http\Controllers\Api\License;

use App\License\AppLicense;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use DateTime;
use Illuminate\Support\Facades\Validator;


class AppLicenseController extends Controller
{

    public  function getVanSalesLicense(Request $request){
        //validate data
        $validator = Validator::make($request->all(),[
            'imei'=> 'required',
            'app_id'=> 'required|numeric',
            'key'=> 'required|numeric'

        ]);
        if ($validator->fails()){
            return response()->json(['error'=>$validator->errors()],416);
        }
        else{
            //check if machine id exist in db
            $isExist = DB::table('app_licenses')->where('imei',$request->imei)
                ->where('van_sales',$request->app_id)
                ->first();

            if ($isExist){
                //get the license data base on imei ,app_id and license key
                $isValidKey = DB::table('app_licenses')->where('imei',$request->imei)
                    ->where('key',$request->key)
                    ->where('van_sales',$request->app_id)
                    ->first();

                //check if license record exist
                if ($isValidKey){
                    //if 1st time sync license
                    if ($isValidKey->sync == 0){

                        return $this->firstTimeLicenseSync($request);

                        //return response()->json('license not yet sync',200);
                    }
                    //if license is already sync
                    else if ($isValidKey->sync == 1){

                        return $this->updateLicenseData($request);
                    }
                    else{

                        return response()->json('sorry! sync status does not match',404);
                    }
                }
                else{

                    return response()->json('sorry! license key does not match',404);
                }

            }

            return response()->json('sorry! machine id does not exist.',404);
        }

    }

    /*
     * function for 1st time license sync
     */
    public function firstTimeLicenseSync(Request $request){
        //get license data from db
        $data = AppLicense::where('imei',$request->imei)
            ->where('key',$request->key)
            ->where('van_sales',$request->app_id)
            ->first();

        //get current date for activation
        $activation_date = date('Y-m-d');
        //$activation_date = new Date();
        //get the current time
        $last_sync_time = date('H:i:s a');
        //get the last day of month
        $last_day_month = date('t',strtotime($activation_date));
        //get the current validity in month
        $validity = $data->validity;
        //if validity is  12 month then add extra 5 days to make 365 days
        if ($validity == 12){
            $validity = $validity * 30 + 5;
            //calculate expiry date base on validity
            $expiry_date = date('Y-m-d', strtotime($activation_date. ' + '. $validity. 'days'));
        }
        // else we multiply validity with 30 to get no of days
        else{
            $validity = $validity * $last_day_month;
            //calculate expiry date base on validity
            $expiry_date = date('Y-m-d', strtotime($activation_date. ' + '. $validity. 'days'));
        }

        $data->activation_date = $activation_date;
        $data->expiration_date = $expiry_date;
        $data->remaining_days = $validity;
        $data->last_sync = $activation_date;
        $data->last_sync_time = $last_sync_time;
        $data->sync = 1;
        $data->save();

        return response()->json($data,200);
    }

    /*
     * function for license is already sync
     */

    public function updateLicenseData(Request $request){
        //get license data from db
        $data = AppLicense::where('imei',$request->imei)
            ->where('key',$request->key)
            ->where('van_sales',$request->app_id)
            ->first();
        //get the current time
        $last_sync_time = date('H:i:s a');
        //get expiry date for license
        $expiry_date = $data->expiration_date;
        //get current date
        $current_date = date('Y-m-d');
        //calculate license remaining days
        $start_date = date_create($current_date);
        $end_date = date_create($expiry_date);
        $remaining_days = date_diff($start_date,$end_date)->format("%a");

        if ($expiry_date > $current_date){

            $data->remaining_days = $remaining_days;
            $data->last_sync = $current_date;
            $data->last_sync_time = $last_sync_time;
            $data->save();

            return response()->json($data,200);
        }
        else {

            $remaining_days = "-".$remaining_days;
            $data->remaining_days = $remaining_days;
            $data->last_sync = $current_date;
            $data->last_sync_time = $last_sync_time;
            $data->save();

            return response()->json($data,406);
        }


    }


}
