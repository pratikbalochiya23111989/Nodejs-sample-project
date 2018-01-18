<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
header('Content-Type: application/json; Charset=UTF-8');

class Webservices extends Front_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('webservices_model', '', TRUE);
		$base_url = $this->config->item('base_url');
	}

	function TestStripePayment(){
		require_once 'stripe/init.php';
		$api_key = $this->fetch_stripe_account_api_key();
		\Stripe\Stripe::setApiKey($api_key);

		$myCard = array('number' => '4111111111111111', 'exp_month' => 10, 'exp_year' => 2018);

		$amt = 10*100;

		try {
			$charge = \Stripe\Charge::create(array('card' => $myCard, 'amount' => $amt, 'currency' => 'cad'));
		} catch(\Stripe\Error\Card $e) {
		  // Since it's a decline, \Stripe\Error\Card will be caught
		  $body = $e->getJsonBody();
		  $err  = $body['error'];

		} catch (\Stripe\Error\RateLimit $e) {
		  // Too many requests made to the API too quickly
			$body = $e->getJsonBody();
			$err  = $body['error'];
		} catch (\Stripe\Error\InvalidRequest $e) {
		  // Invalid parameters were supplied to Stripe's API
			
			$body = $e->getJsonBody();
			$err  = $body['error'];
			

		} catch (\Stripe\Error\Authentication $e) {
		  // Authentication with Stripe's API failed
		  // (maybe you changed API keys recently)

			$body = $e->getJsonBody();
			$err  = $body['error'];

		} catch (\Stripe\Error\ApiConnection $e) {
		  // Network communication with Stripe failed

			$body = $e->getJsonBody();
			$err  = $body['error'];

		} catch (\Stripe\Error\Base $e) {
		  // Display a very generic error to the user, and maybe send
		  // yourself an email

			$body = $e->getJsonBody();
			$err  = $body['error'];

		} catch (Exception $e) {
		  // Something else happened, completely unrelated to Stripe
			$body = $e->getJsonBody();
			$err  = $body['error'];
		}

		if($err['type']=='invalid_request_error'){
			$dataarr['data'] = $err['message'];
			$dataarr['msg'] = 'Failure';
		}
		else {
			$dataarr['data'] = $charge->id;
			$dataarr['msg'] = 'Success';
		}

		$datajson = json_encode($dataarr);
		echo $datajson;exit;
	}

	function DriverRatingToCustomer(){
		if ($_REQUEST['iTripId'] && $_REQUEST['iRating']) {
			$iTripId = $_REQUEST['iTripId'];
			$iRating = $_REQUEST['iRating'];
			$trip_status = $this->webservices_model->check_trip_exists($iTripId);
			if($trip_status=='exist'){
				$ratingStatusCheck = $this->webservices_model->checkDriverRatingStatusExist($iTripId);
				if ($ratingStatusCheck['eDriverRatingStatus'] == 'No') {
					$totalrows = $this->webservices_model->driver_update_trip_rating($iTripId,$iRating);
					$data['rating'] = $iRating;
					$data['msg'] = "Success";
				}else{
					$data['rating'] = $iRating;
					$data['msg'] = "Already Rated";
				}
			}else {
				$data['msg'] = "Trip Not Exist";
			}
		}else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CheckPaymentStatusForFinishedTrip(){
		if($this->input->post('iTripId')){
			$trip_id = $this->input->post('iTripId');
			$status = $this->webservices_model->check_trip_exists($trip_id);
			if($status=='exist'){
				$paymentdata = $this->webservices_model->get_all_payment_information_for_finished_trip($trip_id);
				// $paymentdata['fFinalPayment'] = '$'.' '.number_format($paymentdata['fFinalPayment'],2);
				$paymentdata['fFinalPayment'] = $paymentdata['vCurrencySymbol'].' '.number_format($paymentdata['fFinalPayment'],2);
				$data['data'] = $paymentdata;
				$data['msg'] = "Success";
			}else {
				$data['msg'] = "Trip Not Exist";
			}
		}else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetStarByTrip(){
		if($this->input->post('iTripId')){
			$trip_id = $this->input->post('iTripId');
			$status = $this->webservices_model->check_trip_exists($trip_id);
			if($status=='exist'){
				$stararr['rating'] = number_format($this->webservices_model->get_star_by_trip($trip_id),2);
				$data['data'] = $stararr;
				$data['msg'] = "Success";
			}else {
				$data['msg'] = "Trip Not Exist";
			}
		}else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function AddEncodedString(){
		$status = $this->webservices_model->add_encoded_string($this->input->post('encoded_str_manual'));
		$data['msg'] = "Success";
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function convertcreditcard(){
		$no = '4111111111111111';
		$credit_card_no = $this->encrypt_text($no);
		echo ' converted no: '.$credit_card_no;exit;
	}

	function SendNotificationAfterRideShare(){
		if($this->input->post('iClientId')){
			$client_id = $this->input->post('iClientId');
			// check trip exist or not
			$status = $this->webservices_model->check_client_exists($client_id);
			if($status=='exist'){
				$device_id = $this->webservices_model->get_client_device_details($client_id);
				$pushNotificationData['action'] = 'sendNotification';
				$pushNotificationData['msg'] = "Your Trip has been shared successfully";
				$pushNotificationData['vDeviceid'] = $device_id;
				$pushNotificationData['eUserType'] = "Rider";

				$datapush = $this->pushNotification($pushNotificationData);
				$data['msg'] = "Success";
			}
			else {
				$data['msg'] = "Customer Not Exist";
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CustomerLogout(){
		if($this->input->post('iClientId')){
			$iClientId = $_REQUEST['iClientId'];
			$update = $this->webservices_model->updateCustomerLatitudeLongitudeAndLogout($iClientId);
			$data['msg'] = "Success";
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function PressDriverWaitingForPickup(){
		if($this->input->post('iTripId')){
			$trip_id = $this->input->post('iTripId');
			// check trip exist or not
			$status = $this->webservices_model->check_trip_exists($trip_id);
			if($status=='exist'){
				$device_id = $this->webservices_model->get_trip_client_device_details($trip_id);
				$pushNotificationData['action'] = 'sendNotification';
				$pushNotificationData['msg'] = "Driver waiting outside";
				$pushNotificationData['vDeviceid'] = $device_id;
				$pushNotificationData['eUserType'] = "Rider";
				$datapush = $this->pushNotification($pushNotificationData);

				$totroes = $this->webservices_model->update_press_driver_reach_at_customer_location($trip_id);
				$data['msg'] = "Success";
			}
			else {
				$data['msg'] = "Trip Not Exist";
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function IdleUserDisable(){
		// Cron for Idle User Disable : call after 30 mins
		$current_datetime = date('Y-m-d H:i:s');
		
		// code for customer idle disable
		$all_customer_record = $this->webservices_model->get_all_customer_records();
		if(!empty($all_customer_record)){
			foreach ($all_customer_record as $key => $value) {
				$datetime1 = strtotime($value['dtLatLongUpdateDateTime']);
				$datetime2 = strtotime($current_datetime);
				$interval  = abs($datetime2 - $datetime1);
				$minutes   = round($interval / 60);
				if($minutes>30){
					$status = $this->webservices_model->disable_customer_lat_long($value['iClientId']);        
				}
			}
		}
		//end of code for customer idle disable

		// code for driver idle disable
		/*$all_driver_record = $this->webservices_model->get_all_driver_vehicle_records();
		if(!empty($all_driver_record)){
			foreach ($all_driver_record as $key => $value) {
				$datetime1 = strtotime($value['dtLatLongUpdateDateTime']);
				$datetime2 = strtotime($current_datetime);
				$interval  = abs($datetime2 - $datetime1);
				$minutes   = round($interval / 60);

				if($minutes>30){
					$status = $this->webservices_model->disable_driver_lat_long($value['iDriverId']);        
					$totrows = $this->webservices_model->put_driver_to_logout_mode($value['iDriverId']);        
				}
			}
		}*/
		//end of code for driver idle disable
	}

	function GetNewSingleTripDetails(){
		if($this->input->post('iTripId')){
			$trip_id = $this->input->post('iTripId');
			// check trip exist or not
			$status = $this->webservices_model->check_trip_exists($trip_id);
			$mainarr = array();
			if($status=='exist'){
				$trip_details = $this->webservices_model->get_new_single_trip_details($trip_id);
				$vehicle_detail=$this->webservices_model->get_one_vehicle_detail_byId($trip_details['iVehicleCompanyId']);
				$mainarr['iTripId'] = $trip_id;
				$mainarr['vPickupLocation'] = $trip_details['vPickupLocation'];
				$mainarr['vDestinationLocation'] = $trip_details['vDestinationLocation'];
				// $mainarr['fFinalPayment'] = $this->data['CURRENCY'].$trip_details['fFinalPayment'];
				$mainarr['fFinalPayment'] = $trip_details['vCurrencySymbol'].$trip_details['fFinalPayment'];
				$mainarr['eType'] = $trip_details['eType'];
				$mainarr['eTripType'] = $trip_details['eTripType'];
				$mainarr['vCompany'] = $vehicle_detail['vCompany'];
				$mainarr['vRoundOption'] = $trip_details['vRoundOption'];
				$mainarr['fDistance'] = $trip_details['fDistance'].' '.$trip_details['eDistanceUnit'];
				$mainarr['eTripLocation'] = $trip_details['eTripLocation'];
				if ($mainarr['eTripLocation']=='LocalByDuration' && $mainarr['eTripType']=='Round') {
					$mainarr['additional_per_KMs_Miles_rate']= $trip_details['vCurrencySymbol'].$trip_details['fPerMileFare'];
					$mainarr['additional_per_Hr_fare'] = $trip_details['vCurrencySymbol'].($trip_details['fPerMinFare']*60);
				}
				if ($mainarr['eTripLocation']=='OutStationByDuration' && $mainarr['eTripType']=='Round') {
					$mainarr['additional_per_KMs_Miles_rate']= $trip_details['vCurrencySymbol'].$trip_details['fPerMileFare'];
					$mainarr['additional_per_Hr_fare'] = $trip_details['vCurrencySymbol'].($trip_details['fPerMinFare']*60);
				}
				$mainarr['eDistanceUnit'] = $trip_details['eDistanceUnit'];
				$dTripDate = date_create($trip_details['dTripDate']);
				$dToDate = date_create($trip_details['dToDate']);
				$mainarr['dTripDate'] = date_format($dTripDate, 'jS M Y h:i:s A');
				$mainarr['dToTripDate'] = ($trip_details['dToDate']!='0000-00-00 00:00:00') ? date_format($dToDate, 'jS M Y h:i:s A') : '' ;
				$mainarr['eStatus'] = $trip_details['eStatus'];
				$mainarr['eDriverAssign'] = $trip_details['eDriverAssign'];
				$mainarr['eStartTrip'] = $trip_details['eStartTrip'];
				$mainarr['CustomerFirstName'] = $trip_details['vFirstName'];
				$mainarr['CustomerLastName'] = $trip_details['vLastName'];

				$mainarr['CustomerLatitude'] = $trip_details['dLatitude'];
				$mainarr['CustomerLongitude'] = $trip_details['dLongitude'];

				$startlatlongarr = explode('|', $trip_details['tPickUpAddressLatLong']);
				$endlatlongarr = explode('|', $trip_details['tDestinationAddressLatLong']);

				$mainarr['vPickupLocation_Latitude'] = $startlatlongarr[0];
				$mainarr['vPickupLocation_Longitude'] = $startlatlongarr[1];

				$mainarr['vDestinationLocation_Latitude'] = $endlatlongarr[0];
				$mainarr['vDestinationLocation_Longitude'] = $endlatlongarr[1];
				$mainarr['eGenderPreference'] = $trip_details['eGenderPreference'];
				$mainarr['eSmokingPreference'] = $trip_details['eSmokingPreference'];

				if($trip_details['eDriverAssign']=='Yes'){
					$driver_details = $this->webservices_model->get_driver_details_by_new_trip_id($trip_id);
					$mainarr['iDriverId'] = $driver_details['iDriverId'];
					$mainarr['DriverFirstName'] = $driver_details['DriverFirstName'];
					$mainarr['DriverLastName'] = $driver_details['DriverLastName'];
					$mainarr['DriverLatitude'] = $driver_details['dLatitude'];
					$mainarr['DriverLongitude'] = $driver_details['dLongitude'];
				}
				else {
					$mainarr['iDriverId'] = '';
					$mainarr['DriverFirstName'] = '';
					$mainarr['DriverLastName'] = '';
					$mainarr['DriverLatitude'] = '';
					$mainarr['DriverLongitude'] = '';
				}
				$data['data'] = $mainarr;
				$data['msg'] = "Success";
			}
			else {
				$data['msg'] = "Trip Not Exist";
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CheckTimeForBookLater(){
		if($_REQUEST['selected_time']){

			$current_datetime = date('Y-m-d H:i:s');

			$datetime1 = strtotime($current_datetime);
			$datetime2 = strtotime($_REQUEST['selected_time']);
			$interval  = abs($datetime2 - $datetime1);
			$minutes   = round($interval / 60);
			if($minutes>120){
				$data['msg'] = "No Check For Car";    
			}
			else {
				$data['msg'] = "Check For Car";    
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function auto_login(){
		$id = $this->input->post('id');
		$role=$this->input->post('role');
		if($role=='driver'){
			$this->autodriverLogin($id);
		}
		else if($role=='rider'){
			$this->autoriderLogin($id,$this->input->post('latitude'),$this->input->post('longitude'));
		}else if($role=='owner'){
			$this->autoOwnerLogin($id);
		}
		else{   
			$Data['msg'] = "Role Is Not Valid";
			header('Content-type: application/json');
			$callback = '';
			if (isset($_REQUEST['callback'])){
				$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
			}
			$main = json_encode($Data);
			echo $callback . ''.$main.'';
			exit;
		}   
	}

	function DriverLogOut(){
		if($_REQUEST['iDriverId']){
			$iDriverId = $_REQUEST['iDriverId'];
			$status = $this->webservices_model->update_login_status_by_id($iDriverId);
			$updateLatLog = $this->webservices_model->updateDriverLatitudeAndLongitude($iDriverId);
			$data['msg'] = "Success";
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CheckDriverTripRunning(){
		if($_REQUEST['iDriverId']){
			$iDriverId = $_REQUEST['iDriverId'];
			$all_trips_by_driver = $this->webservices_model->checkRunningTripByDriver($iDriverId);
			if($all_trips_by_driver == 0){
				$data['msg'] = 'Allowed';       
			}
			else {
				$data['msg'] = 'Kindly complete your current Ride.';    
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CheckTripStatus(){
		if($_REQUEST['iTripId']){
			$iTripId = $_REQUEST['iTripId'];
			$status = $this->webservices_model->get_trip_status($iTripId);    
			if($status!='no'){
				$mainarr['trip_status'] = $status;
				$data['data'] = $mainarr;
				$data['msg'] = 'Success';    
			}
			else{
				$data['msg'] = "Trip Not Exist";    
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function SendNotificationForBookLater_New(){
		// Cron for Book Later : Call after 5 mins
		$current_datetime = date('Y-m-d H:i:s');
		$all_trips_info = $this->webservices_model->get_all_book_later_trip_details();

		foreach ($all_trips_info as $keysall => $valuesall) {
			$iTripId = $valuesall['iTripId'];
			$curlatlonarr = $this->webservices_model->get_client_lat_long_arr($iTripId);
			$cur_lat = $curlatlonarr['dLatitude'];
			$cur_long = $curlatlonarr['dLongitude'];
			$car_id = $valuesall['iVehicleCompanyId'];

			$datetime1 = strtotime($current_datetime);
			$datetime2 = strtotime($valuesall['dTripDate']);
			$interval  = abs($datetime2 - $datetime1);
			$minutes   = round($interval / 60);
			$totalhours = intval($minutes/60);

			if(($minutes<=120) && ($valuesall['eBookLaterNotification']=='No')){
				$totrows = $this->webservices_model->update_booklater_notification_status($iTripId);
				$all_models_details = $this->webservices_model->get_all_models_details_by_companyid($car_id);
				$radius_in_kms = $this->webservices_model->get_radius_settings_kms();
				
				if(count($all_models_details)>0){
					foreach ($all_models_details as $key => $value) {
						$all_attribute_details = $this->webservices_model->get_all_models_attribute_details_by_modelid($value['iModelId']);
						if(count($all_attribute_details)>0){
							foreach ($all_attribute_details as $key => $values) {
								$latitude = $values['dLatitude'];
								$longitude = $values['dLongitude'];
								$startlatlong = $cur_lat.'|'.$cur_long;
								$finishlatlong = $latitude.'|'.$longitude;

								$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong_webservice($startlatlong,$finishlatlong);
								if($finalkmminarr!='none'){
									if($finalkmminarr['duplicate_distance']<$radius_in_kms){
										$iVehicleAttributeId = $values['iVehicleAttributeId'];
										$mainarr['iDriverId'] = $this->webservices_model->get_driver_id_by_attribute_id($iVehicleAttributeId);

										$countRunningTrip = $this->webservices_model->checkRunningTripByDriver($mainarr['iDriverId']);
										
										if($countRunningTrip==0){
											$mainarr['iTripId'] = $iTripId;
											$status = $this->webservices_model->check_trip_and_driver($mainarr);
											if($status=='not exist'){
												$this->webservices_model->add_trip_and_driver($mainarr);
											}

											// code for sending push notification
											$driver_device_details = $this->webservices_model->get_driver_device_details($mainarr['iDriverId']);
											if($driver_device_details){
												$pushNotificationData['action'] = 'sendNotification';
												$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$iTripId;
												$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
												$pushNotificationData['eUserType'] = "Driver";
												$datapush = $this->pushNotification($pushNotificationData);
											}
										} // end of if
									}
								}
							}
						} // end of vehicle attributes if
					}
				} // end of models if
			} // end of main if
		}
	}

	function RejectTripByDriver(){
		if($_REQUEST['iDriverId'] && $_REQUEST['iTripId']){
			$iDriverId = $_REQUEST['iDriverId'];
			$iTripId = $_REQUEST['iTripId'];
			$trip_status = $this->webservices_model->get_trip_accept_status($iTripId);
			if($trip_status=='No'){
				$driver_trip_status = $this->webservices_model->check_driver_trip_status($iDriverId,$iTripId);
				if($driver_trip_status=='exist'){
					$totalrows = $this->webservices_model->reject_driver_trip($iDriverId,$iTripId);

					// check no of driver assign
					$noofdriver = $this->webservices_model->get_no_of_driver_assign_to_trip($iTripId);                    
					if($noofdriver==0){
						// send notification to customer
						$device_no = $this->webservices_model->get_client_device_no($iTripId);                    
						$pushNotificationData['action'] = 'sendNotification';
						$pushNotificationData['msg'] = "No Service Available at this Time";
						$pushNotificationData['vDeviceid'] = $device_no;
						$pushNotificationData['eUserType'] = "Rider";
						$datapush = $this->pushNotification($pushNotificationData);

						$totrow = $this->webservices_model->delete_trip_after_rejected_by_all_driver($iTripId);
						// end of code for send notification to customer
					}
					// end of code for no of driver assign

					$data['msg'] = "Success";
				}
				else {
					$data['msg'] = "Sorry, Driver has not assigned to this trip";
				}
			}
			else {
				$data['msg'] = "Sorry, This Trip is already accepted by another driver";
			} //end of else
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function LicenceRenewNotification(){
		// Cron for Licence Renew Notification : call at day ends
		$current_date = date('Y-m-d');
		$alldriverlicence = $this->webservices_model->get_all_drivers_licence_information();
		// $cronRes = $this->webservices_model->save_data(array('dExecuteTime'=> date('Y-m-d H:i:s'),'vNote'=>'LicenceRenewNotification'), 'cron_test');
		foreach ($alldriverlicence as $key => $value) {
			$date1=date_create($value['vLicenceExpiryDate']);
			$date2=date_create($current_date);
			$diff=date_diff($date2,$date1);
			$licenceexpdays = $diff->format("%R%a");
			if($licenceexpdays=='+30'){
				// alert driver
				$driverdevicedetails = $this->webservices_model->get_driver_device_details($value['iDriverId']);
				$pushNotificationData['action'] = 'sendNotification';
				//$pushNotificationData['msg'] = "Hello ".$value['vFirstName']." ".$value['vLastName']." Your License seems to have been expiring on ".$value['vLicenceExpiryDate'].". Please update your new License expiration details within next 15 days to avoid Suspension of your Account. Please share necessary documentation at admin@ridein.com";
				$pushNotificationData['msg'] = "License is about to expire!";
				$pushNotificationData['vDeviceid'] = $driverdevicedetails['device_id'];
				$pushNotificationData['eUserType'] = "Driver";
				$datapush = $this->pushNotification($pushNotificationData);
				// end of code for alert driver
			}
			else if($licenceexpdays=='+15'){
				// make driver account inactive
				$totalrows = $this->webservices_model->update_driver_account_to_inactive($value['iDriverId']);
				// end of code for make driver account Inactive
			}
		}
	}

	function GetAllAvailablePromoCode(){
		if($_REQUEST['iClientId']){
			$iClientId = $_REQUEST['iClientId'];
			$allpromocodes = $this->webservices_model->get_all_promocode_by_user($iClientId);
			$promoarr = array();
			foreach ($allpromocodes as $key => $value) {
				$discount = '';
				if($value['eDiscountType']=='Amount'){
					$discount = $this->data['CURRENCY'].$value['fDiscount'].' DISCOUNT';
				}
				else {
					$discount = $value['fDiscount'].'% DISCOUNT';
				}   
				$promoarr['all_promo_codes'][$key] = $value['vPromotionCode'].' - ['.$discount.']';
			}
			if(count($promoarr)>0){
				$data['data'] = $promoarr;
				$data['msg'] = "Success";
			}
			else {
				$data['data'] = [];
				$data['msg'] = "Failure";
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function UpdateCustomerLocation(){
		$mainarr['iClientId'] = $_REQUEST['iClientId'];
		$mainarr['dLatitude'] = $_REQUEST['dLatitude'];
		$mainarr['dLongitude'] = $_REQUEST['dLongitude'];
		$data['data'] = $mainarr;
		$data['msg'] = "Success";
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
		// ************** function is below **************
		/*if($_REQUEST['iClientId']) {
			$iClientId = $_REQUEST['iClientId'];
			$dLatitude = $_REQUEST['dLatitude'];
			$dLongitude = $_REQUEST['dLongitude'];
			$currentdatetime = date('Y-m-d H:i:s');
			$latlongarr = array("dLatitude"=>$dLatitude,"dLongitude"=>$dLongitude,"dtLatLongUpdateDateTime"=>$currentdatetime);
			$status = $this->webservices_model->check_rider_exists($iClientId);
			if($status=='exist'){
				$totalrows = $this->webservices_model->update_customer_location_latlong($iClientId,$latlongarr);
				$mainarr['iClientId'] = $iClientId;
				$mainarr['dLatitude'] = $dLatitude;
				$mainarr['dLongitude'] = $dLongitude;
				$data['data'] = $mainarr;
				$data['msg'] = "Success";
			}
			else {
				$data['msg'] = "Customer Not Exist";
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;*/
	}

	function PressPanic(){
		if ($_REQUEST['iClientId']) {
			$iClientId = $_REQUEST['iClientId'];
			$status = $this->webservices_model->check_rider_exists($iClientId);
			if($status=='exist'){

				$current_date = date('Y-m-d H:i:s');

				$pressstatusarr = $this->webservices_model->check_press_panic($iClientId);
				$checkflag = 1;
				foreach ($pressstatusarr as $key => $value) {

					$to_time = strtotime($current_date);
					$from_time = strtotime($value['dtAddedOn']);
					$finalmins = round(abs($to_time - $from_time) / 60,2);
					if($finalmins<5){
						$checkflag = 0;
					}
				}

				$my_base_url = $this->data['base_url'];

				if($checkflag==0){
					$data['msg'] = "We have already received your Emergency Request.";
				}
				else {
					$adminemail = $this->webservices_model->get_admin_email_address();
					$customer_info = $this->webservices_model->get_client_personal_information($iClientId);
					$panicdata['iClientId'] = $iClientId;
					$panicdata['dtAddedOn'] = date('Y-m-d H:i:s');
					$iCustomerPanicId = $this->webservices_model->save_panic_info($panicdata);

					$fullname = $customer_info['vFirstName'].' '.$customer_info['vLastName'];

					$bodyArr = array("#NAME#","#EMAIL#","#MOBILE#","#IMAGE_URL#");
					$postArr = array($fullname,$customer_info['vEmail'],$customer_info['iMobileNo'],$my_base_url);

					/*echo '<pre>';
					print_r($bodyArr);
					echo '<pre>';
					print_r($postArr);exit;*/

					$sendAdmin=$this->Send("PANIC_ADMIN","Client",$adminemail,$bodyArr,$postArr); 

					$bodyArr2 = array("#NAME#","#IMAGE_URL#");
					$postArr2 = array($fullname,$my_base_url);
					$sendAdmin=$this->Send("PANIC_CUSTOMER","Client",$customer_info['vEmail'],$bodyArr2,$postArr2); 
					$data['msg'] = "We have received your Emergency Request. We would reach you shortly !";
				} // end of else 
			}
			else {
				$data['msg'] = "Customer Not Exist";
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function TripRating(){
		if ($_REQUEST['iTripId'] && $_REQUEST['iRating']) {
			$iTripId = $_REQUEST['iTripId'];
			$iRating = $_REQUEST['iRating'];
			$trip_status = $this->webservices_model->check_trip_exists($iTripId);
			if($trip_status=='exist'){
				$ratingStatusCheck = $this->webservices_model->checkRatingStatusExist($iTripId);
				if ($ratingStatusCheck['eRatingStatus'] == 'No') {
					$totalrows = $this->webservices_model->update_trip_rating($iTripId,$iRating);
					$data['rating'] = $iRating;
					$data['msg'] = "Success";
				}else{
					$data['rating'] = $iRating;
					$data['msg'] = "Allready Rated";    
				}
			}
			else {
				$data['msg'] = "Trip Not Exist";
			}    
			
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetProfileInformation(){
		if($_REQUEST['role']){
			$role = $_REQUEST['role'];
			if($role=='driver'){
				if($_REQUEST['iDriverId']){
					// check exist or not
					$iDriverId = $_REQUEST['iDriverId'];
					$status = $this->webservices_model->check_driver_exists($iDriverId);
					if($status=='exist'){
						$all_driver_trips = $this->webservices_model->get_all_driver_trips($iDriverId);

						$total_driver_trips = count($all_driver_trips);
						$total_rating_cnt = 0;
						foreach ($all_driver_trips as $key => $value) {
							$total_rating_cnt = ($total_rating_cnt + $value['fRating']);
						}

						$driverDetail = $this->webservices_model->get_driver_details($iDriverId);
						if(!empty($driverDetail)){
							// echo '<pre>';print_r($driverDetail);
							$car_details = $this->webservices_model->get_car_details($driverDetail['iDriverId']);
							if (strpos($driverDetail['iMobileNo'], '-') !== false) {
								$iMobileNo=explode('-',$driverDetail['iMobileNo']);
								$driverDetail['vCountryMobileCode']=$iMobileNo[0];
								$driverDetail['iMobileNo']=$iMobileNo[1];
							}		
							if(!empty($car_details)){
								$driverDetail['vCompany'] = $car_details['vCompany'];
								$driverDetail['vModelName'] = $car_details['vModelName'];
								$driverDetail['vPlateNo'] = $car_details['vRegistrationNo'];
							}else{
								$driverDetail['vCompany'] = '';
								$driverDetail['vModelName'] = '';
								$driverDetail['vPlateNo'] = '';
							}

							$driverDetail['average_rating'] = number_format(($total_rating_cnt/$total_driver_trips),2);
							$driverDetail['vDriverFullname'] = $driverDetail['vFirstName'].' '.$driverDetail['vLastName'];
			
							if($driverDetail['vProfileImage']){
								$driverDetail['image_Url'] = $this->data['base_url'].'uploads/driver/'.$iDriverId.'/'.$driverDetail['vProfileImage'];
							}
							else {
								$driverDetail['image_Url'] = $this->data['base_url'].'uploads/red-driver.png';
							}
							$driverDetail['eAvailability']=($driverDetail['eAvailability']=='Both')?'Both (Local / Outstation)':$driverDetail['eAvailability'];

							if($driverDetail['vState']==''){
								$driverDetail['vState']='';
							}
							if($driverDetail['iStateId']==''){
								$driverDetail['iStateId']='';
							}
							if($driverDetail['vCountry']==''){
								$driverDetail['vCountry']='';
							}
							$data['data'] = $driverDetail;
							//echo "<pre>";print_r($data['data']);exit();
							$data['msg'] = "Success";
						}
						else{
							$data['msg'] = "Your account is Inactive";  
						}
					}
					else {
						$data['msg'] = "Driver Not Exist";  
					}
					// end of code
				}
				else {
					$data['msg'] = "Failure";
				}
			}
			else if($role=='rider'){
				if($this->input->post('iClientId')){
					// check exist or not
					$iClientId = $this->input->post('iClientId');
					$status = $this->webservices_model->check_rider_exists($iClientId);         
					if($status=='exist'){
						$clientDetail = $this->webservices_model->get_single_client_details($iClientId);
						$clientDetail['fullname'] = $clientDetail['vFirstName'].' '.$clientDetail['vLastName'];         
						if(!empty($clientDetail)){
							if (strpos($clientDetail['iMobileNo'], '-') !== false) {
								$iMobileNo=explode('-',$clientDetail['iMobileNo']);
								$clientDetail['vCountryMobileCode']=$iMobileNo[0];
								$clientDetail['iMobileNo']=$iMobileNo[1];
							}

							if ($clientDetail['vProfileImage']) {
								$clientDetail['image_Url'] = $this->data['base_url'].'uploads/client/'.$clientDetail['iClientId'].'/'.$clientDetail['vProfileImage'];
							}else{
								$clientDetail['image_Url'] = $this->data['base_url'].'uploads/plash-holder.png';
							}
							
							if(isset($clientDetail['tAddress'])){
								$clientDetail['tAddress'] = $clientDetail['tAddress'];
							}
							else {
								$clientDetail['tAddress'] = "";
							}

							$clientAllTrips = $this->webservices_model->get_customer_all_trips($iClientId);
							$ratingCtr = $totalRating = 0;
							foreach ($clientAllTrips as $ctkey => $trip) {
								if ($trip['eDriverRatingStatus']=='Yes') {
									$totalRating+=$trip['fDriverRating'];
									$ratingCtr++;
								}
							}
							$clientDetail['average_rating'] = ($ratingCtr == 0) ? '0' : number_format(($totalRating/$ratingCtr),2);
							// $clientDetail['average_rating'] = number_format(($totalRating/$ratingCtr),2);
							// echo "No of Rat :".$ratingCtr." --> total Rat * : ".$totalRating;exit;
								// $this->printthisexit($trip['fDriverRating']);
							$clientDetail['vPassword']= $this->decrypt($clientDetail['vPassword']);
							$data['data']= $clientDetail;
							$data['msg'] = "Success";
						}
						else {
							$data['msg'] = "Your account is Inactive";  
						}
					}
					else{
						$data['msg'] = "Customer Not Exist";    
					}
					// end of code
				}
				else {
					$data['msg'] = "Failure";
				}
			} // end of code
			else {
				$data['msg'] = "Failure";
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CheckDriverAssignToTrip(){
		if($this->input->post('iTripId')){
			$trip_id = $this->input->post('iTripId');
			$driver_assign_status = $this->webservices_model->check_driver_assign_status_to_trip($trip_id);
			if($driver_assign_status!='assign'){
				$statusarr['status'] = "Driver Not Assign To Trip";
				$data['data'] = $statusarr;
				$data['msg'] = "Success";
			}
			else {
				$statusarr['status'] = "Driver Already Assign To Trip";
				$data['data'] = $statusarr;
				$data['msg'] = "Success";
			}           
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CheckNewsLetterPromotionCode(){
		if($this->input->post('newsletter_promo_code') && $this->input->post('iClientId')){
			$iClientId = $this->input->post('iClientId');
			$newsletter_promo_code = $this->input->post('newsletter_promo_code');
			$code_status = $this->webservices_model->check_code_exist($newsletter_promo_code);  

			if($code_status=='exist'){
				$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
				$promo_code_assigned = $this->webservices_model->check_promocode_assigned_user($iClientId,$promoinfo['iPrmotionCodeId']);
			
				if ($promo_code_assigned == 'assigned') {
					$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);
					$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
					if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
						$promo_usage_status = $this->webservices_model->check_newsletter_promo_code_used_by_client($iClientId,$promoinfo['iPrmotionCodeId']);
						if($promo_usage_status =='exist'){
							$codestatus['status'] = "This Promotion Code was already utilized!";
							$data['data'] = $codestatus;
							$data['msg'] = "Failure";
						}else {
							$promo_data =$this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
							if($promo_data){
								$codestatus = array();
								$totalfare = $this->input->post('totalfare');
								$fare_currency = $this->input->post('fare_currency');
								// $totalfare = str_replace('$', "", $totalfare);
								$totalfare = str_replace(',', "", $totalfare);
								$totalfare = floatval($totalfare);
								$codestatus['status'] = "Valid";
								if($promoinfo['eDiscountType']=='Percentage'){
									$codestatus['discount_msg'] = "You have successfully used the Promotion Code. You have received a discount of ".$promoinfo['fDiscount']."%";
									$discount = ($totalfare * floatval($promoinfo['fDiscount']))/100;
									if ($discount >= $totalfare ) {
										$codestatus['fare_before_discount'] = $fare_currency.$totalfare;
										$codestatus['discount_amount'] = $fare_currency.$totalfare;
										$codestatus['fare_after_discount'] = $fare_currency.'0';
									} else {
										$codestatus['fare_before_discount'] = $fare_currency.$totalfare;
										$codestatus['discount_amount'] = $fare_currency.$discount;
										$netFare = $totalfare - $discount;
										$codestatus['fare_after_discount'] = $fare_currency.(number_format($netFare, 2));
									}
								}
								else {
									$codestatus['discount_msg'] = "You have successfully used the Promotion Code. You have received a discount of  ".$fare_currency.$promoinfo['fDiscount'];
									$discount = floatval($promoinfo['fDiscount']);
									if ($discount >= $totalfare ) {
										$codestatus['fare_before_discount'] = $fare_currency.$totalfare;
										$codestatus['discount_amount'] = $fare_currency.$totalfare;
										$codestatus['fare_after_discount'] = $fare_currency.'0';
									} else {
										$codestatus['fare_before_discount'] = $fare_currency.$totalfare;
										$codestatus['discount_amount'] = $fare_currency.$discount;
										$netFare = $totalfare - $discount;
										$codestatus['fare_after_discount'] = $fare_currency.(number_format($netFare, 2));
									}
								}
								$data['data'] = $codestatus;
								$data['msg'] = "Success";
							}
							else {
								$codestatus['status'] = "Invalid Promotion Code!";
								$data['data'] = $codestatus;
								$data['msg'] = "Failure";
							}
						}
					}else {
						$codestatus['status'] = "Invalid Promotion Code!";
						$data['data'] = $codestatus;
						$data['msg'] = "Failure";
					}
				} else {
					$codestatus['status'] = "Invalid Promotion Code!";
					$data['data'] = $codestatus;
					$data['msg'] = "Failure";
				}
			}
			else {
				$codestatus['status'] = "Invalid Promotion Code!";
				$data['data'] = $codestatus;
				$data['msg'] = "Failure";
			}
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function SendAlertMessageNotificationToRider_New(){
		$current_date = date('Y-m-d H:i:s');
		$from_time = strtotime($current_date);
		
		$all_trips = $this->webservices_model->get_all_trips_for_sending_alert_notification();
		foreach ($all_trips as $key => $value) {

			if($value['ePressDriverReachBtn']=='No'){
				$to_time = strtotime($value['dTripDate']);
				$totalmins = round(abs($to_time - $from_time) / 60,0);
				
				if($totalmins<30){
					$rider_devices = $this->webservices_model->get_device_details_of_rider($value['iClientId']);
					$driver_vehicle_details = $this->webservices_model->get_driver_vehicle_details($value['iTripId']);
					$driverfullname = $driver_vehicle_details['vFirstName'].' '.$driver_vehicle_details['vLastName'];

					$startlatlong = $rider_devices['dLatitude'].'|'.$rider_devices['dLongitude'];
					$finishlatlong = $driver_vehicle_details['dLatitude'].'|'.$driver_vehicle_details['dLongitude'];
					$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong_webservice($startlatlong,$finishlatlong);

					$pushNotificationData['action'] = 'sendNotification';
					if($finalkmminarr['original_duration']=='n'){
						$pushNotificationData['msg'] = "Driver Arrived";
					}
					else {
						$pushNotificationData['msg'] = $finalkmminarr['original_duration']." for Pick Up";
					}
					$pushNotificationData['vDeviceid'] = $rider_devices['device_id'];
					$pushNotificationData['eUserType'] = "Rider";
					$datapush = $this->pushNotification($pushNotificationData); 
				}
			}
		}
	}

	function GetCashCreditCardDetails(){
		if($this->input->post('iClientId')){
			$iClientId = $this->input->post('iClientId');
			$credit_card_data = $this->webservices_model->get_credit_card_details($iClientId);
			if($credit_card_data){
				$credit_card_origin_no = $this->decrypt_text($credit_card_data['vCreditcardNo']);
				$credit_card_arr['payment_option'] = "XXXX XXXX XXXX ".substr($credit_card_origin_no,-4,4);
				$data['data'] = $credit_card_arr;
				$data['msg'] = "Success";
			}
			else {
				$credit_card_arr['payment_option'] = 'Cash Payment';    
				$data['data'] = $credit_card_arr;
				$data['msg'] = "Success";
			}           
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function PickUpRiderNow_20_06_2016(){
		if($this->input->post('iTripId')){
			$iTripId = $this->input->post('iTripId');
			$status = $this->webservices_model->check_trip_exists($iTripId);
			if($status=='exist'){
				$totalrows = $this->webservices_model->update_pickup_ride($iTripId);
				$data['msg'] = "Success";
				// send notification to customer
				$trip_full_details = $this->webservices_model->get_single_trip_details($iTripId);
				$customer_device_info = $this->webservices_model->get_rider_device_details_by_id($trip_full_details['iClientId']);
				$pushNotificationData['action'] = 'sendNotification';
				$pushNotificationData['msg'] = "Driver ".$trip_full_details['vFirstName'].' '.$trip_full_details['vLastName'].' is waiting outside for your ride !';
				$pushNotificationData['vDeviceid'] = $customer_device_info['device_id'];
				$pushNotificationData['eUserType'] = "Rider";
				$datapush = $this->pushNotification($pushNotificationData);
				// end of code for send notification
			}
			else {
				$data['msg'] = "Trip Not Exist";
			}           
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function PickUpRiderNow(){
		if($this->input->post('iTripId')){
			$iTripId = $this->input->post('iTripId');
			$status = $this->webservices_model->check_trip_exists($iTripId);
			if($status=='exist'){
				$trip_full_details = $this->webservices_model->get_single_trip_details($iTripId);
				// $this->printthisexit($trip_full_details);
				if($trip_full_details['eBookType']=='Later'){
					$onecitydetail = $this->webservices_model->cityDetailByTripID($iTripId);
					$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
					$current_time = $tmpDate->format('Y-m-d H:i:s');

					$datetime1 = strtotime($current_time);
					$datetime2 = strtotime($trip_full_details['dTripDate']);
					$remain_time  = $datetime2 - $datetime1;
					$minutes   = round($remain_time / 60);
					if ($minutes > 60) {
						// $data['msg'] = "Pickup time for this trip is ".$trip_full_details['dTripDate'];
						$data['msg'] = 'Trip is NOT ready for pickup now';
						echo json_encode($data);
						exit;
					}
				}

				$totalrows = $this->webservices_model->update_pickup_ride($iTripId);
				$data['msg'] = "Success";
				// send notification to customer
				$customer_device_info = $this->webservices_model->get_rider_device_details_by_id($trip_full_details['iClientId']);
				$pushNotificationData['action'] = 'sendNotification';
				$pushNotificationData['msg'] = "Driver ".$trip_full_details['vFirstName'].' '.$trip_full_details['vLastName'].' is waiting outside for your ride !';
				$pushNotificationData['vDeviceid'] = $customer_device_info['device_id'];
				$pushNotificationData['eUserType'] = "Rider";
				$datapush = $this->pushNotification($pushNotificationData);
				// end of code for send notification
			}
			else {
				$data['msg'] = "Trip Not Exist";
			}           
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetAllTripPartners(){
		if($this->input->post('iTripId')){
			$iTripId = $this->input->post('iTripId');

			$status = $this->webservices_model->check_trip_exists($iTripId);
			if($status=='exist'){
				$mainarr = array();
				$all_trip_info = $this->webservices_model->get_single_trip_details($iTripId);

				$mainarr['iClientId'] = $all_trip_info['iClientId'];
				$mainarr['vClientFullName'] = $all_trip_info['ClientFirstName'].' '.$all_trip_info['ClientLastName'];
				$mainarr['vPickupLocation'] = $all_trip_info['vPickupLocation'];
				$mainarr['vDestinationLocation'] = $all_trip_info['vDestinationLocation'];
				

				$all_trip_partners = $this->webservices_model->get_all_trip_partners_info($all_trip_info['iClientId'],$iTripId);

				$totaltrippartnercnt = count($all_trip_partners);
				$mainarr['TotalPartner'] = "$totaltrippartnercnt";

				foreach ($all_trip_partners as $key => $value) {
					$mainarr['partner_details'][$key]['iPartnerId'] = $value['iClientId'];
					$mainarr['partner_details'][$key]['vPartnerFullName'] = $value['vFirstName'].' '.$value['vLastName'];   
					if($value['vProfileImage']){
						$mainarr['partner_details'][$key]['vPartnerProfileURL'] = $this->data['base_upload'].'client/'.$value['iClientId'].'/'.$value['vProfileImage'];
					}
					else {
						$mainarr['partner_details'][$key]['vPartnerProfileURL'] = $this->data['base_upload'].'plash-holder.png';
					}
					$mainarr['partner_details'][$key]['eRequestStatus'] = $value['eRequestStatus'];
				}

				if(count($mainarr)>0){
					$data['data'] = $mainarr;
					$data['msg'] = "Success";
				}
				else {
					$data['data'] = 'No Record Found';
					$data['msg'] = "Success";   
				}
			}
			else {
				$data['msg'] = "Trip Not Exist";
			}   
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetAddressFromLatLong($latitude,$longitude){
		// Google Keys
		$randarr = array('AIzaSyD3y465iAxiPKDV9UdMGKPpJ5bFU7-FSRI','AIzaSyAgqpPxh0o9MlzCCGuB37aF8Eq8lO2LQSU','AIzaSyDrRdwYoebtJiwV3kt_WdocewktXyEq6dE','AIzaSyDAynSVP_VZaLEmVTQ__0U1zefuQuyHiV8','AIzaSyBe4Eq8REk12MaOhoPzHtf5FcqHDgjK4AY','AIzaSyAYDRHJANc38AqZUpF5_mUB2EFvWPLOAE0','AIzaSyCkF3Z9dsfKOgUsWzNJWmrS_B-7-VvqK5o','AIzaSyCyCakXahv0DtjDMwK-mcVk8MEWwzqoSOg');
		$k = array_rand($randarr);
		
		$key = $randarr[$k];

		$lat = $latitude;
		$lng = $longitude;
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false&key='.$key;
		
		$json = @file_get_contents($url);
		$resdata=json_decode($json);
		
		$status = $resdata->status;
		if($status=="OK"){
			return $resdata->results[0]->formatted_address;
		}
		else {
			return "";
		}
	}

	function GetAddressFromLatitudeLongitude(){
		if($this->input->post('dLatitude') && $this->input->post('dLongitude')){
			$lat = $this->input->post('dLatitude');
			$lng = $this->input->post('dLongitude');
			// Google Keys
			$randarr = array('AIzaSyD3y465iAxiPKDV9UdMGKPpJ5bFU7-FSRI','AIzaSyAgqpPxh0o9MlzCCGuB37aF8Eq8lO2LQSU','AIzaSyDrRdwYoebtJiwV3kt_WdocewktXyEq6dE','AIzaSyDAynSVP_VZaLEmVTQ__0U1zefuQuyHiV8','AIzaSyBe4Eq8REk12MaOhoPzHtf5FcqHDgjK4AY','AIzaSyAYDRHJANc38AqZUpF5_mUB2EFvWPLOAE0','AIzaSyCkF3Z9dsfKOgUsWzNJWmrS_B-7-VvqK5o','AIzaSyCyCakXahv0DtjDMwK-mcVk8MEWwzqoSOg');
			shuffle($randarr);
			$k = array_rand($randarr);

			$key = $randarr[$k];

			$url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false&key='.$key;
			$json = @file_get_contents($url);
			$resdata=json_decode($json);
			/*echo '<pre>';
			print_r($resdata);exit;*/
			$status = $resdata->status;
			if($status=="OK"){
				$data['address'] = $resdata->results[0]->formatted_address;
				$data['city'] = $this->GetCityFromAddressOrLatLong($resdata->results[0]->formatted_address,'address');
				$data['msg'] = "Success";
			}
			else {
				$data['msg'] = "Failure";    
			}
		} // end of if
		else {
			$data['msg'] = "Failure";
		} // end of else
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function StartTripTime(){
		if($this->input->post('iTripId') && $this->input->post('dLatitude') && $this->input->post('dLongitude')){
			$pickup_latitude = $this->input->post('dLatitude');
			$destination_longitude = $this->input->post('dLongitude');
			$trip_id = $this->input->post('iTripId');
			$trip_data['vPickupLocation'] = $this->GetAddressFromLatLong($pickup_latitude,$destination_longitude);
			// $trip_data['dTripDate'] = date('Y-m-d H:i:s');
			//---------
			$tripCityDetail = $this->webservices_model->cityDetailByTripID($trip_id);
			$driverTime = new DateTime('now', new DateTimeZone($tripCityDetail['vTimeZone']));
			$trip_data['dTripDate'] = $driverTime->format('Y-m-d H:i:s');
			//---------
			$trip_data['eStartTrip'] = 'Yes';
			$trip_data['tPickUpAddressLatLong'] = $pickup_latitude.'|'.$destination_longitude;
			$trip_data['TrackerStartLatlong'] = $pickup_latitude.','.$destination_longitude;
			$trip_data['TrackerFinishLatlong'] = $pickup_latitude.','.$destination_longitude;
			$trip_data['fDistance'] = 0;
			$totalupdatedrows = $this->webservices_model->update_trip_details($trip_id,$trip_data);
			$data['msg'] = 'Success';
			// send notification to customer
			$trip_full_details = $this->webservices_model->get_single_trip_details($trip_id);
			$customer_device_info = $this->webservices_model->get_rider_device_details_by_id($trip_full_details['iClientId']);
			$pushNotificationData['action'] = 'sendNotification';
			$pushNotificationData['msg'] = 'Your trip has just started !';
			$pushNotificationData['vDeviceid'] = $customer_device_info['device_id'];
			$pushNotificationData['eUserType'] = "Rider";
			$datapush = $this->pushNotification($pushNotificationData);
			// end of code for send notification
		}
		else {
			$data['msg'] = 'Failure';
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetSingleTripDetails(){
		if($this->input->post('iTripId')){
			$trip_id = $this->input->post('iTripId');
			// check trip exist or not
			$status = $this->webservices_model->check_trip_exists($trip_id);
			if($status=='exist'){
				$trip_data = $this->webservices_model->get_single_trip_details($trip_id);
				$mycurrency = $this->webservices_model->get_city_currency($trip_data['iCityId']);
				$trip_data['minimum_fare'] = $mycurrency['vCurrencySymbol'].$trip_data['MinimumFare'];
				$fare_details=$this->webservices_model->getdurationFulDetails($trip_data['iVehicleCompanyId'],$trip_data['iCityId'],$trip_data['eTripLocation']);
				$trip_data['fMinimumKm']=$fare_details['fMinimumKm'];
				$fare_details['lOptions']=explode(',',$fare_details['lOptions']);

				foreach ($fare_details['lOptions'] as $key => $value) {
					$fare=explode('|', $value);
					if($fare[0]==$trip_data['vRoundOption']){
						$trip_data['Fare']=$mycurrency['vCurrencySymbol'].$fare[1];
						$trip_data['Option']=$fare[0];
					}
				}
				$trip_data['fRating'] = number_format($trip_data['fRating'],2);
				$trip_data['fDriverRating'] = number_format($trip_data['fDriverRating'],2);

				$trip_data['vClientFullName'] = $trip_data['ClientFirstName'].' '.$trip_data['ClientLastName'];

				$trip_data['Customer_Current_Latitude'] = $trip_data['dLatitude'];
				$trip_data['Customer_Current_Longitude'] = $trip_data['dLongitude'];
				unset($trip_data['dLatitude']);
				unset($trip_data['dLongitude']);
				
				if($trip_data['ePaymentType']=='Credit Card'){
					$credit_card_info = $this->webservices_model->get_card_full_info($trip_data['iCustomerCreditCardId']);
					$credit_card_origin_no = $this->decrypt_text($credit_card_info['vCreditcardNo']);
					$trip_data['vCreditcardNo'] = "XXXX XXXX XXXX ".substr($credit_card_origin_no,-4,4);
					$base_path = $this->data['base_path'];
					$file_path = $base_path.'uploads/credit_card/'.$credit_card_info['vCardImage'];
					
					if (file_exists($file_path)) {
						$trip_data['image_url'] = $this->data['base_upload'].'credit_card/'.$credit_card_info['vCardImage'];
					}else{
						$trip_data['image_url'] = 'No_image_available';
					}
				}
				else {
					$trip_data['ePaymentType'] = "Cash Payment";
					$trip_data['image_url'] = '';
				}
					
				$all_partners_names = $this->webservices_model->get_all_trip_partners_info_driver($trip_id);
				if(count($all_partners_names)==1){
					$partnernames = 'You rode with customer '.$all_partners_names[0]['vFirstName'].' '.$all_partners_names[0]['vLastName'].' and '.$trip_data['ClientFirstName'].' '.$trip_data['ClientLastName'];  
				}
				else if(count($all_partners_names)>1){
					for($i=0;$i<count($all_partners_names);$i++){
						if(($i==0) && (count($all_partners_names)!=2)){
							$partnernames = 'You rode with customer '.$partnernames.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'].',';
						}
						else if(($i==0) && (count($all_partners_names)==2)){
							$partnernames = 'You rode with customer '.$partnernames.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'];
						}
						else if($i==(count($all_partners_names)-1)){
							$partnernames = $partnernames.','.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'].' and '.$trip_data['ClientFirstName'].' '.$trip_data['ClientLastName'];
						}
						else if($i==(count($all_partners_names)-2)){
							$partnernames = $partnernames.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'];
						}
						else {
							$partnernames = $partnernames.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'].',';   
						}
					}   
				}
				else if(count($all_partners_names)==0){
					$partnernames = 'You rode with customer '.$trip_data['ClientFirstName'].' '.$trip_data['ClientLastName'];
				}

				$trip_data['partner_names'] = $partnernames;
				$trip_data['driver_name'] = 'You rode with driver '.$trip_data['vFirstName'].' '.$trip_data['vLastName'];

				$startpointlatlonarr = explode('|', $trip_data['tPickUpAddressLatLong']);
				$trip_data['vPickupLocation_Latitude'] = $startpointlatlonarr[0];
				$trip_data['vPickupLocation_Longitude'] = $startpointlatlonarr[1];
				unset($trip_data['tPickUpAddressLatLong']);

				$finishpointlatlonarr = explode('|', $trip_data['tDestinationAddressLatLong']);
				$trip_data['vDestinationLocation_Latitude'] = $finishpointlatlonarr[0];
				$trip_data['vDestinationLocation_Longitude'] = $finishpointlatlonarr[1];
				unset($trip_data['tDestinationAddressLatLong']);
				if($trip_data['eTripLocation']=='OutStationByDuration'){
					$trip_data['fDistancefare'] = $trip_data['fPerMileFare']*$trip_data['fDistance'];
					$trip_data['fDistancefare'] = $mycurrency['vCurrencySymbol'].$trip_data['fDistancefare'];
				}else{
					$trip_data['fDistancefare'] = $mycurrency['vCurrencySymbol'].$trip_data['fPerMileFare'];
				}

				$trip_data['fDistance'] = $trip_data['fDistance']." ".strtoupper($trip_data['eDistanceUnit']);
				
				
				$trip_data['fBaseFare'] = $mycurrency['vCurrencySymbol'].$trip_data['fBaseFare'];
				$trip_data['fPerMileFare'] = $mycurrency['vCurrencySymbol'].$trip_data['fPerMileFare'];
				// change distance fare


				$trip_data['fPerMinFare'] = $mycurrency['vCurrencySymbol'].$trip_data['fPerMinFare'];
				$trip_data['fServiceTax'] = $mycurrency['vCurrencySymbol'].$trip_data['fServiceTax'];

				$trip_original_start_date = $trip_data['dTripDate'];
				$trip_original_end_date = $trip_data['dRideEndDate'];

				$trip_data['trip_start_full_time'] = date('H:i:s',strtotime($trip_data['dTripDate']));

				$tripstartdate = date_create($trip_data['dTripDate']);
				$dToDate = date_create($trip_data['dToDate']);
				$trip_data['dTripDate'] = date_format($tripstartdate, 'jS M Y h:i:s A');
				$trip_data['dToTripDate'] = ($trip_data['dToDate'] !='0000-00-00 00:00:00') ? date_format($dToDate, 'jS M Y h:i:s A') : '' ;
				$tripenddate = date_create($trip_data['dRideEndDate']);
				$trip_data['dRideEndDate'] = date_format($tripenddate, 'jS F Y');


				$tripstarttime = date_create($trip_original_start_date);
				$trip_data['dTripStartTime'] = date_format($tripstarttime, 'g:i A');

				if($trip_original_end_date!='0000-00-00 00:00:00'){
					$tripendtime = date_create($trip_original_end_date);
					$trip_data['dRideEndDate'] = date_format($tripendtime, 'g:i A');
				}
				else {
					$trip_data['dRideEndDate'] = "";
				}

				if($trip_original_end_date!='0000-00-00 00:00:00'){
					$tripendtime = date_create($trip_original_end_date);
					$trip_data['dTripEndTime'] = date_format($tripendtime, 'g:i A');
				}
				else {
					$trip_data['dTripEndTime'] = '';    
				}
				
				/*$trip_data['dTripFullStartTime'] = date('H:i:s',$trip_original_start_date);
				$trip_data['dTripFullStartTime'] = date_format($trip_data['dTripFullStartTime'], 'jS F Y g:i A');*/
				$trip_data['fTotalMinute'] = ceil($trip_data['fTotalMinute']);
				if($trip_data['fTotalMinute']<=1){
					$trip_data['fTotalMinute'] = ceil($trip_data['fTotalMinute']).' MIN';
				}
				else {
					$fTotalMinute = ceil($trip_data['fTotalMinute']);
					if($fTotalMinute>=60){
						$hours = intval($fTotalMinute/60);
						$minutes = $fTotalMinute - ($hours * 60);

						if($hours>1){
							if($minutes>1){
								$trip_data['fTotalMinute'] = $hours.' HRS '.$minutes.' MINS';
							}
							else {
								$trip_data['fTotalMinute'] = $hours.' HRS '.$minutes.' MIN';
							}
						}
						else{
							if($minutes>1){
								$trip_data['fTotalMinute'] = $hours.' HR '.$minutes.' MINS';
							}
							else {
								$trip_data['fTotalMinute'] = $hours.' HR '.$minutes.' MIN';
							}
						}
					}
					else {
						$trip_data['fTotalMinute'] = ceil($trip_data['fTotalMinute']).' MINS';
					}
				}
				if($trip_data['eTripLocation']=='Shuttle'){
					$shuttleInfo = explode('|', $trip_data['vShuttleInfo']);
					$shType=explode(',', $shuttleInfo[0]);
					$shFare=explode(',', $shuttleInfo[1]);
					// Adult
					$adultFare = $shType[0]*$shFare[0];
					$trip_data['adultFare'] = $mycurrency['vCurrencySymbol'].number_format($adultFare, 2);
					// Chield
					$childFare = $shType[1]*$shFare[1];
					$trip_data['childFare'] = $mycurrency['vCurrencySymbol'].number_format($childFare, 2);
					// Infant
					$infantFare = $shType[2]*$shFare[2];
					$trip_data['infantFare'] = $mycurrency['vCurrencySymbol'].number_format($infantFare, 2);
					// Pet
					$petFare = $shType[3]*$shFare[3];
					$trip_data['petFare'] = $mycurrency['vCurrencySymbol'].number_format($petFare, 2);
					// Bag
					$total_bags = ($shType[4] > 2) ? ($shType[4]-2): 0 ;
					$bagFare = $total_bags*$shFare[4];
					$trip_data['bagFare'] = $mycurrency['vCurrencySymbol'].number_format($bagFare, 2);
					// Hand Bag
					$total_hand_bags = ($shType[5] > 1) ? ($shType[5]-1): 0 ;
					$handBagFare = $total_hand_bags*$shFare[5];
					$trip_data['handBagFare'] = $mycurrency['vCurrencySymbol'].number_format($handBagFare, 2);
				}else{

				}
				
				$totalinvitepromodic = ($trip_data['dInvitePromoCodeDiscount']+$trip_data['dNewsLetterPromocodeDiscount']);
				if($totalinvitepromodic > $trip_data['dSubtotalPayment']){
					$trip_data['sub_total'] = $mycurrency['vCurrencySymbol'].number_format($trip_data['dSubtotalPayment'], 2);
					$trip_data['total_discounts'] = $mycurrency['vCurrencySymbol'].number_format(($trip_data['dInvitePromoCodeDiscount']+$trip_data['dNewsLetterPromocodeDiscount']),2);
					$trip_data['dSubtotalPayment'] = $mycurrency['vCurrencySymbol'].number_format($trip_data['dSubtotalPayment'],2);
					$trip_data['final_total'] = $mycurrency['vCurrencySymbol'].'0.00';
					$trip_data['fFinalPayment'] = $mycurrency['vCurrencySymbol'].'0.00';
				}
				else{
					$trip_data['sub_total'] = $mycurrency['vCurrencySymbol'].number_format($trip_data['dSubtotalPayment'], 2);
					$trip_data['total_discounts'] = $mycurrency['vCurrencySymbol'].number_format(($trip_data['dInvitePromoCodeDiscount']+$trip_data['dNewsLetterPromocodeDiscount']),2);
					$trip_data['final_total'] = $mycurrency['vCurrencySymbol'].number_format(($trip_data['dSubtotalPayment']-$totalinvitepromodic),2);
					$trip_data['fFinalPayment'] = $mycurrency['vCurrencySymbol'].number_format(($trip_data['dSubtotalPayment']-$totalinvitepromodic),2);
					$trip_data['dSubtotalPayment'] = $mycurrency['vCurrencySymbol'].number_format($trip_data['dSubtotalPayment'],2);
				}

				if($trip_data){

					if($trip_data['vProfileImage']!=''){
						$trip_data['vProfileImage'] = $this->data['base_upload'].'driver/'.$trip_data['iDriverId'].'/'.$trip_data['vProfileImage']; 
					}
					else {
						$trip_data['vProfileImage'] = $this->data['base_upload'].'red-driver.png';
					}

					if($trip_data['ClientProfileImage']!=''){
						$trip_data['ClientProfileImage'] = $this->data['base_upload'].'client/'.$trip_data['iClientId'].'/'.$trip_data['ClientProfileImage'];
					}
					else {
						$trip_data['ClientProfileImage'] = $this->data['base_upload'].'plash-holder.png';   
					}

					$data['data'] = $trip_data;
					$data['msg'] = 'Success';
				}
				else {
					$data['msg'] = 'Trip Not Exist';
				}
			} // end of main if
			else {
				$data['msg'] = 'Trip Not Exist';
			}
			// end of code for trip exist or not
		}
		else {
			$data['msg'] = 'Failure';
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function dateDiff($date)
	{
	  $mydate= date("Y-m-d H:i:s");
	  $theDiff="";
	  $datetime1 = date_create($date);
	  $datetime2 = date_create($mydate);
	  $interval = date_diff($datetime1, $datetime2);
	  $min = $interval->format('%i');
	  return $min;
	}

	function StarRating($fRating){
		$red_url = $this->data['base_url'].'assets/image/red-star.png';
		$gray_url = $this->data['base_url'].'assets/image/gray-star.png';
		$finalstarratestr = '';
		$truestar = $fRating;
		$falsestar = (5 - $fRating);
		if($truestar!=0){
			for($i=0; $i < $truestar; $i++) { 
				 $finalstarratestr = $finalstarratestr."<li style='float:left; margin:0 3px 0 0;'><img src='".$red_url."' alt=''/></li>";
			}
		}

		if($falsestar!=0){
			for($i=0; $i < $falsestar; $i++) { 
				 $finalstarratestr = $finalstarratestr."<li style='float:left; margin:0 3px 0 0;'><img src='".$gray_url."' alt=''/></li>";
			}
		}
		return $finalstarratestr;
	}

	function GetDriverDetailsByTrip(){
		if($this->input->post('iTripId')){
			$trip_id = $this->input->post('iTripId');

			$status = $this->webservices_model->check_trip_exists($trip_id);
			if($status=='exist'){
				$mainarr = array();

				$driver_data = $this->webservices_model->get_driver_details_by_trip_id($trip_id);

				$mainarr['eTripLocation'] = $driver_data['eTripLocation'];
				$mainarr['eTripType'] = $driver_data['eTripType'];
				$mainarr['eStartTrip'] = $driver_data['eStartTrip'];
				$mainarr['ePickUpRiderNow'] = $driver_data['ePickUpRiderNow'];
				$mainarr['eDriverAssign'] = $driver_data['eDriverAssign'];

				
				$source_address = mysql_real_escape_string($driver_data['vPickupLocation']);
				$mainarr['vPickupLocation'] = $source_address;
				// $startlatlong = $this->GetLatLongFromAddress($source_address);

				$startlatlongarr = explode('|', $driver_data['tPickUpAddressLatLong']);
				$mainarr['vPickupLocation_Latitude'] = $startlatlongarr[0];
				$mainarr['vPickupLocation_Longitude'] = $startlatlongarr[1];

				$destination_address = mysql_real_escape_string($driver_data['vDestinationLocation']);
				$mainarr['vDestinationLocation'] = $destination_address;
				//$finishlatlong = $this->GetLatLongFromAddress($destination_address);

				if ($driver_data['tDestinationAddressLatLong']!='') {
					$finishpointlatlonarr = explode('|', $driver_data['tDestinationAddressLatLong']);
					$mainarr['vDestinationLocation_Latitude'] = $finishpointlatlonarr[0];
					$mainarr['vDestinationLocation_Longitude'] = $finishpointlatlonarr[1];
				} else {
					$mainarr['vDestinationLocation_Latitude'] = '';
					$mainarr['vDestinationLocation_Longitude'] = '';
				}

				/*$finishlatlongarr = explode('|', $driver_data['tDestinationAddressLatLong']);
				$mainarr['vDestinationLocation_Latitude'] = $finishlatlongarr[0];
				$mainarr['vDestinationLocation_Longitude'] = $finishlatlongarr[1];*/

				$mainarr['fDistance'] = $driver_data['fDistance'].' '.strtoupper($driver_data['eDistanceUnit']);
				$mainarr['fTotalMinute'] = $this->ConvertHrToMin($driver_data['fTotalMinute']);

				$mainarr['iDriverId'] = $driver_data['iDriverId'];
				$mainarr['vFirstName'] = $driver_data['vFirstName'];
				$mainarr['vLastName'] = $driver_data['vLastName'];
				$mainarr['vDriverFullname'] = $mainarr['vFirstName']." ".$mainarr['vLastName'];
				$mainarr['vEmail'] = $driver_data['vEmail'];
				$mainarr['iMobileNo'] = $driver_data['iMobileNo'];


				if($driver_data['vProfileImage']!=''){
					$mainarr['vProfileImage'] = $this->data['base_upload'].'driver/'.$driver_data['iDriverId'].'/'.$driver_data['vProfileImage'];   
				}
				else {
					$mainarr['vProfileImage'] = $this->data['base_upload'].'red-driver.png';
				}

				$car_data = $this->webservices_model->get_car_details_by_driverid($driver_data['iDriverId']);           

				
				$mainarr['vCompany'] = $car_data['vCompany'];
				$mainarr['vPlateNo'] = $car_data['vRegistrationNo'];
				$mainarr['iYear'] = $car_data['iYear'];
				$mainarr['dLatitude'] = $car_data['dLatitude'];
				$mainarr['dLongitude'] = $car_data['dLongitude'];
				$mainarr['vModelName'] = $car_data['vModelName'];
				
				//$mainarr['Model_Image_Url'] = $this->data['base_upload']."150x150.png";
				$mainarr['Model_Image_Url'] = $this->data['base_upload']."car_model/".$car_data['iModelId']."/".$car_data['vModelimage'];

				$single_trip_details = $this->webservices_model->get_single_trip_details($trip_id);

				if($single_trip_details){
					if($single_trip_details['iRideId']==0){
						$mainarr['trip_type'] = 'Unfix';
					}
					else {
						$mainarr['trip_type'] = 'Fix';   
					}

					$credit_card_data = $this->webservices_model->get_credit_card_details($single_trip_details['iClientId']);
					if($credit_card_data){
						$credit_card_origin_no = $this->decrypt_text($credit_card_data['vCreditcardNo']);
						$mainarr['payment_option'] = "XXXX XXXX XXXX ".substr($credit_card_origin_no,-4,4);
					}
					else {
						$mainarr['payment_option'] = 'Cash Payment';
					}
				}
				else {
					$mainarr['payment_option'] = '';
				}
				
				if(count($mainarr)>0) {
					$data['data'] = $mainarr;
					$data['msg'] = 'Success';
				}
				else {
					$data['msg'] = 'Driver Not Assigned To Trip';
				}
			}
			else {
				$data['msg'] = 'Trip Not Exist';
			}
		}
		else {
			$data['msg'] = 'Failure';
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function UpdateCarCurrentLocation(){
		if($this->input->post('iDriverId') && $this->input->post('device_id')){
			$driver_id = $this->input->post('iDriverId');
			$driverdevicestatus = $this->webservices_model->check_driver_device_exists($driver_id);
			if($driverdevicestatus=='exist'){
				$driverloginstatus = $this->webservices_model->check_driver_login_status_by_id($driver_id);
				if($driverloginstatus=='Yes'){
					// code for knowing which driver trip exists
	                $trip_records = $this->webservices_model->get_driver_running_trip($driver_id);
	                // echo "<pre>";print_r($trip_records);exit;
	                if(!empty($trip_records)){
	                    $iTripId = $trip_records['iTripId'];
	                    $tracker_record = $this->webservices_model->get_trip_tracker_latlong($iTripId);
	                    if(!empty($tracker_record)){
	                    	if(empty($tracker_record['TrackerFinishLatlong'])){
	                            $tracker_data['TrackerFinishLatlong'] = $this->input->post('latitude').','.$this->input->post('longitude');
	                            $totalrows = $this->webservices_model->update_trip_details($iTripId,$tracker_data);
	                        }
	                        else {
	                            $tracker_data['TrackerStartLatlong'] = $tracker_record['TrackerFinishLatlong'];
	                            $tracker_data['TrackerFinishLatlong'] = $this->input->post('latitude').','.$this->input->post('longitude');
	                            $totalrows = $this->webservices_model->update_trip_details($iTripId,$tracker_data);
	                        }

	                        // code for adding distance live
	                        $final_tracker_record = $this->webservices_model->get_trip_tracker_latlong($iTripId);
					
	                        $startlatlongarr = explode(',', $final_tracker_record['TrackerStartLatlong']);
	                        $finishlatlongarr = explode(',', $final_tracker_record['TrackerFinishLatlong']);

	                        $lat1 = $startlatlongarr[0];
	                        $lon1 = $startlatlongarr[1];
	                        $lat2 = $finishlatlongarr[0];
	                        $lon2 = $finishlatlongarr[1];

	                        $theta = $lon1 - $lon2;
	                        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	                        $dist = acos($dist);
	                        $dist = rad2deg($dist);
	                        if ($final_tracker_record['eDistanceUnit']=='Miles') {
	                        	$totalkm = ($dist * 60 * 1.1515);
	                        }
	                        if ($final_tracker_record['eDistanceUnit']=='KMs') {
	                        	$miles = ($dist * 60 * 1.1515);
	                        	$totalkm = ($miles * 1.609344);
	                        }
	                        $finalkms = ($totalkm + $final_tracker_record['fDistance']);
	                        $finalkmarr['fDistance'] = $finalkms;
	                        $totalrows = $this->webservices_model->update_trip_details($iTripId,$finalkmarr);
	                    }
	                }
					$location['dLatitude'] = $this->input->post('latitude');
					$location['dLongitude'] = $this->input->post('longitude');
					$location['dtLatLongUpdateDateTime'] = date('Y-m-d H:i:s');
					$totalrows = $this->webservices_model->update_car_location($driver_id,$location);
					$mainarr['iDriverId'] = $driver_id;
					$mainarr['dLatitude'] = $location['dLatitude'];
					$mainarr['dLongitude'] = $location['dLongitude'];
					$data['data'] = $mainarr;
					$data['msg'] = 'Success';
				}else{
					$data['msg'] = 'You can log out';
				}
			}
			else {
				$data['msg'] = 'You can log out';
			}
		}
		else {
			$data['msg'] = 'Failure';
		}
		//************** Close the function execution **************

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetMileKMFromLatLong($startlatlong,$finishlatlong){
		$lat1 = $long1 = $lat2 = $long2 = 0;
		
		$startlatlongarr = explode('|', $startlatlong);
		$lat1 = $startlatlongarr[0];
		$lon1 = $startlatlongarr[1];

		$finishlatlongarr = explode('|', $finishlatlong);
		$lat2 = $finishlatlongarr[0];
		$lon2 = $finishlatlongarr[1];
		// d = acos( sin(lat1)*sin(lat2) + cos(lat1)*cos(lat2)*cos(lon2-lon1) ) * R

		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;

		$totalkms = number_format(($miles * 1.609344),2);
		$result['kms']= round($totalkms);
		$result['miles']= round($miles);
		return $result;
	}

	function GetTimeInMinsFromDistance($distance){
		$speed_of_car = 25;
		$time = number_format((($distance/$speed_of_car)*60),2);
		return $time;
	}

	function ConvertHrToMin($totalmins){
		$originalmins = $totalmins;
		$totalmins = intval($totalmins);
		$newhr = 0;
		if($totalmins==0){
			$newtime = '0 MIN';
		}
		else if($totalmins==1){
			$newtime = '1 MIN';
		}
		else if($totalmins>=60){
			if($totalmins==60){
				$newtime = '1 HR';
			}
			else{
				$tmp = intval($totalmins/60);
				$min = ($totalmins%60);
				if($tmp>1){
					if($min){
						if($min==1){
							$newtime = $tmp.' HRS '.$min.' MIN';       
						}
						else {
							$newtime = $tmp.' HRS '.$min.' MINS';
						}
					}
					else{
						$newtime = $tmp.' HRS ';         
					}
				}
				else{
					if($min){
						if($min==1){
							$newtime = $tmp.' HR '.$min.' MIN';        
						}
						else{
							$newtime = $tmp.' HR '.$min.' MINS';
						}
					}
					else{
						$newtime = $tmp.' HR ';         
					}
				}
			}
		}
		else{
			$newtime = $totalmins.' MINS';
		}

		return $newtime;



		/*if($totalmins==0){
			$totalmins = '0 min';
		}
		else if($totalmins==60){
			$totalmins = '1 hr';
		}
		else if($totalmins<60){
			$totalmins = $totalmins.' mins';
		}
		else {
			$hrs = intval($totalmins/60);
			$mins = ($totalmins%60);
			$totalmins = $hrs.' hr '.$mins.' mins';
		}
		return $totalmins;*/
	}

	function Get_DISTANCE_DURATION_FromLatLong_webservice($startlatlong,$finishlatlong){

		$startlatlong = str_replace('|',',', $startlatlong);
		$finishlatlong = str_replace('|',',', $finishlatlong);
		// Google Keys
		$randarr = array('AIzaSyD3y465iAxiPKDV9UdMGKPpJ5bFU7-FSRI','AIzaSyAgqpPxh0o9MlzCCGuB37aF8Eq8lO2LQSU','AIzaSyDrRdwYoebtJiwV3kt_WdocewktXyEq6dE','AIzaSyDAynSVP_VZaLEmVTQ__0U1zefuQuyHiV8','AIzaSyBe4Eq8REk12MaOhoPzHtf5FcqHDgjK4AY','AIzaSyAYDRHJANc38AqZUpF5_mUB2EFvWPLOAE0','AIzaSyCkF3Z9dsfKOgUsWzNJWmrS_B-7-VvqK5o','AIzaSyCyCakXahv0DtjDMwK-mcVk8MEWwzqoSOg');
		shuffle($randarr);
		$k = array_rand($randarr);

		$endpoint = 'https://maps.googleapis.com/maps/api/directions/json?';
		
		$source = $startlatlong;
		$destination = $finishlatlong;

		$params   = array(
		  'origin'      => $source,
		  'destination' => $destination,
		  'mode'        => 'driving',
		  'sensor'      => 'false',
		  'units'       => 'km',
		  'key'         => $randarr[$k],
		);

		// Fetch and decode JSON string into a PHP object
		$json = file_get_contents($endpoint.http_build_query($params));
		$jsondata = json_decode($json);

		/*echo '<pre>';
		print_r($jsondata);exit;*/

		$originaldistance = $duplicatedistance = $originalduration = $duplicateduration = $main_distance = $main_duration = '';

		if($jsondata->status=='OK'){    
			$apidistance = strtolower($jsondata->routes[0]->legs[0]->distance->text);
			$apiduration = $jsondata->routes[0]->legs[0]->duration->text;

			$main_distance = $apidistance;
			$main_duration = $apiduration;

			// code for km 
			if (strpos($apidistance,'km') !== false) {
				if (strpos($apidistance,',') !== false) {
					$apidistance = str_replace(',', '', $apidistance);
					$originaldistance = $apidistance;
					$apidistance = str_replace(' ','',$apidistance);
					$duplicatedistance = strtok($apidistance, 'km');
				}
				else {
					$originaldistance = $apidistance;
					$apidistance = str_replace(' ','',$apidistance);
					$duplicatedistance = strtok($apidistance, 'km');
				}
			}
			else if(strpos($apidistance,'mi') !== false) {
				$originaldistance = $apidistance;
				$apidistance = str_replace(' ','',$apidistance);
				$duplicatedistance = strtok($apidistance, 'mi');
				$duplicatedistance = number_format(($duplicatedistance*1.609344),2);
			}
			else if(strpos($apidistance,'m') !== false) {
				$originaldistance = $apidistance;
				$apidistance = str_replace(' ','',$apidistance);
				$duplicatedistance = strtok($apidistance, 'm');
				$duplicatedistance = number_format(($duplicatedistance/1000),2);
			}
			
			// end of code for km
			// code for min
			if ((strpos($main_duration,'hours') !== false) || (strpos($main_duration,'hour') !== false)) {
				$originalduration = $main_duration;

				if(strpos($main_duration,'hours') !== false){
					$totalhours = strtok($main_duration, 'hours');
					$totalhours = str_replace('hours','',$totalhours);
				}
				else if(strpos($main_duration,'hour') !== false){
					$totalhours = strtok($main_duration, 'hour');
					$totalhours = str_replace('hour','',$totalhours);
				}
				$totalhours = intval(str_replace(' ','',$totalhours));
				$duplicateduration = ($totalhours*60);  
				
			}
			else if((strpos($main_duration,'mins') !== false) || ((strpos($main_duration,'min') !== false))){
				$originalduration = $main_duration;
				if(strpos($main_duration,'mins') !== false){
					$totalmins = strtok($main_duration, 'mins');
					$totalmins = str_replace('mins','',$totalmins);
				}
				else if(strpos($main_duration,'min') !== false){
					$totalmins = strtok($main_duration, 'min');
					$totalmins = str_replace('min','',$totalmins);
				}
				$duplicateduration = intval(str_replace(' ','',$totalmins));
			}
			
			$finalkmminarr['original_distance'] = $originaldistance;
			$finalkmminarr['duplicate_distance'] = $duplicatedistance;
			$finalkmminarr['original_duration'] = $originalduration;
			$finalkmminarr['duplicate_duration'] = $duplicateduration;
			return $finalkmminarr;
			// end of code for min
		}
		else {
			return 'none';
		}
	}

	function BookLaterTimeCheck(){
		$now = strtotime(date('Y-m-d H:i:s')); // or your date as well
		$your_date = strtotime($_REQUEST['book_later_time']);

		$datediff = $your_date - $now;
		$totaldaysdiff = floor($datediff/(60*60*24));
		$totaltimediff = floor($datediff/(60));
		
		if($totaltimediff < 120){
			$data['msg'] = 'Booking Time should be 2 Hours later then current time';
		}
		else if($totaldaysdiff>365){
			$data['msg'] = 'Book before 1 year for book later'; 
		}
		else{
			$data['msg'] = 'Success';
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function decrypt_text($value){
	   if(!$value) return false;
	 
	   $crypttext = base64_decode($value);
	   $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, 'SECURE_STRING_1', $crypttext, MCRYPT_MODE_ECB, 'SECURE_STRING_2');
	   return trim($decrypttext);
	}

	function fetch_stripe_account_api_key(){
		$api_key = $this->webservices_model->get_stripe_payment_api_key();
		return trim($api_key);
	}

	function stripepayment($customer_credit_card_id,$rider_id,$final_payment,$currency){
		$riderPersonalDetails = $this->webservices_model->get_client_personal_information($rider_id);
		$riderCreditCardDetails = $this->webservices_model->get_card_full_info($customer_credit_card_id);

		if($riderCreditCardDetails){
			$payment_details['vCardName'] = $riderCreditCardDetails['vCardName'];//'MasterCard';
			$payment_details['vCreditcardNo'] = $this->decrypt_text($riderCreditCardDetails['vCreditcardNo']);//'5555555555554444';
			$payment_details['iCvvNo'] = $riderCreditCardDetails['iCvvNo'];//'321';
			$payment_details['iMonth'] = $riderCreditCardDetails['iMonth'];
			$payment_details['iYear'] = $riderCreditCardDetails['iYear'];
			$payment_details['fFees'] = $final_payment;
			
			$client_details['vFirstName'] = $riderPersonalDetails['vFirstName'];
			$client_details['vLastName'] = $riderPersonalDetails['vLastName'];
			$client_details['vEmail'] = $riderPersonalDetails['vEmail'];

			require_once 'stripe/init.php';

			$api_key = $this->fetch_stripe_account_api_key();

			\Stripe\Stripe::setApiKey($api_key);

			$myCard = array('number' => $payment_details['vCreditcardNo'], 'exp_month' => $payment_details['iMonth'], 'exp_year' => $payment_details['iYear']);

			$error_status = 'no';
			$stripe_final_amount = ($final_payment * 100);

			try {
				if ($currency) {
					$paycurrency = $currency;
				} else {
					$paycurrency = 'USD';
				}
				$charge = \Stripe\Charge::create(array('card' => $myCard, 'amount' => $stripe_final_amount, 'currency' => $paycurrency));
				$error_status = 'no';
			}catch(\Stripe\Error\Card $e) {
				$error_status = 'yes';
				$body = $e->getJsonBody();
				$err  = $body['error'];

			} catch (\Stripe\Error\RateLimit $e) {
				$error_status = 'yes';
				$body = $e->getJsonBody();
				$err  = $body['error'];
			} catch (\Stripe\Error\InvalidRequest $e) {
				$error_status = 'yes';
				$body = $e->getJsonBody();
				$err  = $body['error'];
			} catch (\Stripe\Error\Authentication $e) {
				$error_status = 'yes';
				$body = $e->getJsonBody();
				$err  = $body['error'];

			} catch (\Stripe\Error\ApiConnection $e) {
				$error_status = 'yes';
				$body = $e->getJsonBody();
				$err  = $body['error'];

			} catch (\Stripe\Error\Base $e) {
				$error_status = 'yes';
				$body = $e->getJsonBody();
				$err  = $body['error'];

			} catch (Exception $e) {
				$error_status = 'yes';
				$body = $e->getJsonBody();
				$err  = $body['error'];
			}

			if($error_status=='yes'){
				$stripefinalarr['message'] = $err['message'];
				$stripefinalarr['error_status'] = 'yes';
			}
			else {
				$stripefinalarr['message'] = $charge->id;
				$stripefinalarr['error_status'] = 'no';
			}
			return $stripefinalarr;
		}
		else {
			$stripefinalarr['message'] = 'Credit Card Not Available';
			$stripefinalarr['error_status'] = 'yes';
			return $stripefinalarr;
		}
	}

	function Do_direct_payment_demo($payment_details,$client_details){
		$sandbox = TRUE;    
		$api_username =  'vipulchauhan04-facilitator_api1.gmail.com';
		$api_password = 'DD9CP9L7F9P9JYBG';
		$api_signature = 'AiBGtuxCyADPNqEtL5N.4fNAGDW1ApfFoLxrr5tJzqn9Q8iMJhg9jt9b';
		$api_version = '109.0';
		$application_id = $sandbox ? 'APP-80W284485P519543T' : '';
		$developer_account_email = '';

		$PayPalConfig = array(
					'Sandbox' => $sandbox,
					'APIUsername' => $api_username,
					'APIPassword' => $api_password,
					'APISignature' => $api_signature
					);
		spl_autoload_register(function($class) {
			$file = __DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, array_slice(explode('\\', $class ), 0, -1)).DIRECTORY_SEPARATOR.implode('' , array_slice( explode( '\\' , $class ), -1 , 1)).'.php';
			if(file_exists($file)) {
				include($file);
			}
		});

		/*echo '<pre>';
		print_r($PayPalConfig);*/
		$this->load->library('PayPal/PayPal.php');
		$PayPal = new PayPal($PayPalConfig);



		$DPFields = array(
						'paymentaction' => 'Sale',                         // How you want to obtain payment.  Authorization indidicates the payment is a basic auth subject to settlement with Auth & Capture.  Sale indicates that this is a final sale for which you are requesting payment.  Default is Sale.
						'ipaddress' => $_SERVER['REMOTE_ADDR'],                             // Required.  IP address of the payer's browser.
						'returnfmfdetails' => '1'                     // Flag to determine whether you want the results returned by FMF.  1 or 0.  Default is 0.
					);
					
		$CCDetails = array(
							'creditcardtype' => 'MasterCard',                     // Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
							'acct' => '5424180818927383',                                 // Required.  Credit card number.  No spaces or punctuation.  
							'expdate' => '102016',                             // Required.  Credit card expiration date.  Format is MMYYYY
							'cvv2' => '123',                                 // Requirements determined by your PayPal account settings.  Security digits for credit card.
							'startdate' => '',                             // Month and year that Maestro or Solo card was issued.  MMYYYY
							'issuenumber' => ''                            // Issue number of Maestro or Solo card.  Two numeric digits max.
						);
						
		$PayerInfo = array(
							'email' => $client_details['vEmail'],                                 // Email address of payer.
							'payerid' => '',                             // Unique PayPal customer ID for payer.
							'payerstatus' => '',                         // Status of payer.  Values are verified or unverified
							'business' => 'Testers, LLC'                             // Payer's business name.
						);
						
		$PayerName = array(
							'salutation' => 'Mr.',                         // Payer's salutation.  20 char max.
							'firstname' => $client_details['vFirstName'],                             // Payer's first name.  25 char max.
							'middlename' => '',                         // Payer's middle name.  25 char max.
							'lastname' => $client_details['vLastName'],                             // Payer's last name.  25 char max.
							'suffix' => ''                                // Payer's suffix.  12 char max.
						);
						
		$BillingAddress = array(
								'street' => '123 Test Ave.',                         // Required.  First street address.
								'street2' => '',                         // Second street address.
								'city' => 'Kansas City',                             // Required.  Name of City.
								'state' => 'MO',                             // Required. Name of State or Province.
								'countrycode' => 'US',                     // Required.  Country code.
								'zip' => '64111',                             // Required.  Postal code of payer.
								'phonenum' => '555-555-5555'                         // Phone Number of payer.  20 char max.
							);
							
		$ShippingAddress = array(
								'shiptoname' => 'Tester Testerson',                     // Required if shipping is included.  Person's name associated with this address.  32 char max.
								'shiptostreet' => '123 Test Ave.',                     // Required if shipping is included.  First street address.  100 char max.
								'shiptostreet2' => '',                     // Second street address.  100 char max.
								'shiptocity' => 'Kansas City',                     // Required if shipping is included.  Name of city.  40 char max.
								'shiptostate' => 'MO',                     // Required if shipping is included.  Name of state or province.  40 char max.
								'shiptozip' => '64111',                         // Required if shipping is included.  Postal code of shipping address.  20 char max.
								'shiptocountry' => 'US',                     // Required if shipping is included.  Country code of shipping address.  2 char max.
								'shiptophonenum' => '555-555-5555'                    // Phone number for shipping address.  20 char max.
								);
							
		$PaymentDetails = array(
								'amt' => $payment_details['fFees'],                             // Required.  Total amount of order, including shipping, handling, and tax.  
								'currencycode' => 'CAD',                     // Required.  Three-letter currency code.  Default is USD.
								'itemamt' => ($payment_details['fFees']-5),                         // Required if you include itemized cart details. (L_AMTn, etc.)  Subtotal of items not including S&H, or tax.
								'shippingamt' => '5.00',                     // Total shipping costs for the order.  If you specify shippingamt, you must also specify itemamt.
								'shipdiscamt' => '',                     // Shipping discount for the order, specified as a negative number.  
								'handlingamt' => '',                     // Total handling costs for the order.  If you specify handlingamt, you must also specify itemamt.
								'taxamt' => '',                         // Required if you specify itemized cart tax details. Sum of tax for all items on the order.  Total sales tax. 
								'desc' => 'Web Order',                             // Description of the order the customer is purchasing.  127 char max.
								'custom' => '',                         // Free-form field for your own use.  256 char max.
								'invnum' => '',                         // Your own invoice or tracking number
								'notifyurl' => ''                        // URL for receiving Instant Payment Notifications.  This overrides what your profile is set to use.
							);    
				
		$OrderItems = array();
		$Item     = array(
							'l_name' => 'Test Widget 123',                         // Item Name.  127 char max.
							'l_desc' => 'The best test widget on the planet!',                         // Item description.  127 char max.
							'l_amt' => $payment_details['fFees'],                             // Cost of individual item.
							'l_number' => '85858585',                         // Item Number.  127 char max.
							'l_qty' => '1',                             // Item quantity.  Must be any positive integer.  
							'l_taxamt' => '',                         // Item's sales tax amount.
							'l_ebayitemnumber' => '',                 // eBay auction number of item.
							'l_ebayitemauctiontxnid' => '',         // eBay transaction ID of purchased item.
							'l_ebayitemorderid' => '11'                 // eBay order ID for the item.
					);
		/*array_push($OrderItems, $Item);*/
		
		$Secure3D = array(
						  'authstatus3d' => '', 
						  'mpivendor3ds' => '', 
						  'cavv' => '', 
						  'eci3ds' => '', 
						  'xid' => ''
						  );
						  
		$PayPalRequestData = array(
								'DPFields' => $DPFields, 
								'CCDetails' => $CCDetails, 
								'PayerInfo' => $PayerInfo, 
								'PayerName' => $PayerName, 
								'BillingAddress' => $BillingAddress, 
								'ShippingAddress' => $ShippingAddress, 
								'PaymentDetails' => $PaymentDetails, 
								'OrderItems' => $OrderItems, 
								'Secure3D' => $Secure3D
							);
		
		$PayPalResult = $PayPal -> DoDirectPayment($PayPalRequestData);
		return $PayPalResult;
	}

	function Test_Push_Notification(){
		/*$pushNotificationData['action']  = 'sendNotification';
		$pushNotificationData['msg'] = "Welcome to Onetouchcab Rider";
		$pushNotificationData['vDeviceid'] = "69065F4E-2AEB-4105-A337-20207E874CBF";
		$pushNotificationData['eUserType'] = "Rider";
		$datariderpush = $this->pushNotification($pushNotificationData);
		echo '1. Message Sent to Customer';*/

		/*
		$pushNotificationData['action'] = 'sendNotification';
		$pushNotificationData['msg'] = "Welcome to Onetouchcab Driver";
		$pushNotificationData['vDeviceid'] = "69065F4E-2AEB-4105-A337-20207E874CBF";// 26560c3978306ec8
		$pushNotificationData['eUserType'] = "Driver";
		$data = $this->pushNotification($pushNotificationData);
		echo "\n2. Message Sent to Driver";
		*/

		$pushNotificationData['action'] = 'sendNotification';
		$pushNotificationData['msg'] = "Welcome to Onetouchcab Owner";
		$pushNotificationData['vDeviceid'] = "69065F4E-2AEB-4105-A337-20207E874CBF";
		$pushNotificationData['eUserType'] = "Owner";
		$data = $this->pushNotification($pushNotificationData);
		echo "\n3. Message Sent to Owner";
	}
	// code for register device
	function DeviceRegistration(){
		if($this->input->post('vDevicename') && $this->input->post('vType') && $this->input->post('vDeviceid') && $this->input->post('tDeviceToken') && $this->input->post('users_id') && $this->input->post('users_type')){
			// Push Notification Data
			$puchNotificationData['action'] = 'register';
			$puchNotificationData['vDevicename'] = $this->input->post('vDevicename');
			$puchNotificationData['vType'] = $this->input->post('vType');
			$puchNotificationData['vDeviceid'] = $this->input->post('vDeviceid');

			$user_type_status = $this->input->post('users_type');
			if($user_type_status=='rider'){
				$puchNotificationData['eUserType'] = 'Rider';
				$iAppId = 1;
			}
			else if($user_type_status=='Driver'){
				$iAppId = 2;
				$puchNotificationData['eUserType'] = 'Driver';
			}
			else {
				$iAppId = 3;
				$puchNotificationData['eUserType'] = 'Owner';
			}
			// check in user table vDeviceid is exist
			$devices = $this->webservices_model->get_device_details_by_device_id($iAppId,$puchNotificationData['vDeviceid']);
			if ($devices >0) {
				$devicesRes = $this->webservices_model->delete_device($iAppId,$puchNotificationData['vDeviceid']);
			}

			// check in device_master table vDeviceid is exist
			$devicesMasterCnt = $this->webservices_model->count_device_by_device_id($puchNotificationData['vDeviceid'],$puchNotificationData['eUserType']);
			if ($devicesMasterCnt >0) {
				$devicesMasterRes=$this->webservices_model->delete_same_device($puchNotificationData['vDeviceid'],$puchNotificationData['eUserType']);
			}

			$tDeviceToken = str_replace(' ','', $this->input->post('tDeviceToken'));
			$tDeviceToken = str_replace('<','', $this->input->post('tDeviceToken'));
			$tDeviceToken = str_replace('>','', $this->input->post('tDeviceToken'));
			$puchNotificationData['deviceToken'] = $tDeviceToken;           
			$devicedetails['users_id'] = $this->input->post('users_id');
			$devicedetails['device_id'] = $this->input->post('vDeviceid');
			$devicedetails['device_name'] = $this->input->post('vDevicename');
			$devicedetails['device_type'] = $this->input->post('vType');
			$devicedetails['device_token'] = $this->input->post('tDeviceToken');
			$devicedetails['user_type'] = $this->input->post('users_type');         

			$devicecnt = $this->webservices_model->get_device_details_register($devicedetails['users_id'],$devicedetails['user_type']);
			
			if($devicecnt>0){
				// delete existing device
					$devicecnt = $this->webservices_model->delete_existing_devices($devicedetails['users_id'],$devicedetails['user_type']);
				// end of code for existing devices

				// save new user device
					$id = $this->webservices_model->add_device_detail($devicedetails);
				// end of code for save device  
			}
			else {
				// save new user device
					$id = $this->webservices_model->add_device_detail($devicedetails);
				// end of code for save device      
			}   
			
			$datadevicereg=$this->pushNotification($puchNotificationData);

			if($datadevicereg){
					$newdata['id'] = $datadevicereg['id'];
					$newdata['success'] = $datadevicereg['success'];
					$dataArry = array(
						'data'    => $newdata,
						'message' => $datadevicereg['msg']
					);  
			}
			else{
					$dataArry = array(
					'message' => 'Success'
				);  
			}
		} // end of if
		else {
			$dataArry['msg'] = "Success";
		}
		
		header('Content-type: application/json');
		$main = json_encode($dataArry);
		echo $main.'';
		exit;
	}
	// end of code for register device

	// code for push notification
	function pushNotification($data){
		$url = $this->data['base_url'].'pushnotification/webservice.php';
		if($data['eUserType']=='Rider'){
			$data['project'] = 'ONETOUCHCABCUSTOMER';
		}else if($data['eUserType']=='Driver'){
			$data['project'] = 'ONETOUCHCABDRIVER';
		}else if($data['eUserType']=='Owner'){
			$data['project'] = 'ONETOUCHCABOWNER';
		}else{
			return "error";
		}
		$fields_string = '';
		foreach($data as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		$fields_string = rtrim($fields_string,'&');
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,count($data));
		curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,  CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		// echo 'END : <pre>';print_r($result);exit;
		curl_close($ch);
		return json_decode($result,true);
	}
	// end of code for push notification

	function GetCityFromAddressOrLatLong($val,$type){
		// Google Keys
		$randarr = array('AIzaSyD3y465iAxiPKDV9UdMGKPpJ5bFU7-FSRI','AIzaSyAgqpPxh0o9MlzCCGuB37aF8Eq8lO2LQSU','AIzaSyDrRdwYoebtJiwV3kt_WdocewktXyEq6dE','AIzaSyDAynSVP_VZaLEmVTQ__0U1zefuQuyHiV8','AIzaSyBe4Eq8REk12MaOhoPzHtf5FcqHDgjK4AY','AIzaSyAYDRHJANc38AqZUpF5_mUB2EFvWPLOAE0','AIzaSyCkF3Z9dsfKOgUsWzNJWmrS_B-7-VvqK5o','AIzaSyCyCakXahv0DtjDMwK-mcVk8MEWwzqoSOg');
		shuffle($randarr);
		$k = array_rand($randarr);

		$key = $randarr[$k];

		if($type=='address'){
			$val = urlencode($val);
			$url = "https://maps.google.com/maps/api/geocode/json?address=$val&sensor=false&key=".$key;
		}
		else if($type=='latlong'){
			$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$val&sensor=false&key=".$key;
		}
		
		$result = file_get_contents("$url");
		$json = json_decode($result);

		foreach ($json->results as $result) {
			foreach($result->address_components as $addressPart) {
				$flagcnt = 0;
				foreach ($addressPart->types as $value) {
					if($value=='locality' || $value=='political'){
						$flagcnt++;
					}
				}
				if($flagcnt==2){
					$city = $addressPart->long_name;
				}
			}
		}
		$city = mysql_real_escape_string($city);
		return $city;
	}

	function GetCountryFromAddressOrLatLong($val,$type){
		// Google Keys
		$randarr = array('AIzaSyD3y465iAxiPKDV9UdMGKPpJ5bFU7-FSRI','AIzaSyAgqpPxh0o9MlzCCGuB37aF8Eq8lO2LQSU','AIzaSyDrRdwYoebtJiwV3kt_WdocewktXyEq6dE','AIzaSyDAynSVP_VZaLEmVTQ__0U1zefuQuyHiV8','AIzaSyBe4Eq8REk12MaOhoPzHtf5FcqHDgjK4AY','AIzaSyAYDRHJANc38AqZUpF5_mUB2EFvWPLOAE0','AIzaSyCkF3Z9dsfKOgUsWzNJWmrS_B-7-VvqK5o','AIzaSyCyCakXahv0DtjDMwK-mcVk8MEWwzqoSOg');
		shuffle($randarr);
		$k = array_rand($randarr);

		$key = $randarr[$k];
		if($type=='address'){
			$val = urlencode($val);
			$url = "https://maps.google.com/maps/api/geocode/json?address=$val&sensor=false&key=".$key;
		}
		if($type=='latlong'){
			$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$val&sensor=false&key=".$key;
		}
		
		$result = file_get_contents("$url");
		$json = json_decode($result);
		$country = array();
		foreach ($json->results as $result) {
			foreach($result->address_components as $addressPart) {
				$flagcnt = 0;
				foreach ($addressPart->types as $value) {
					if($value=='country'){
						$country['long_name'] = $addressPart->long_name;
						$country['short_name'] = $addressPart->short_name;
						$flagcnt++;
					}
				}
			}
		}
		return $country;
	}

	function TestAddress(){
		$address = "Swastik Char Rasta, Chimanlal Girdharlal Road, Navrangpura, Ahmedabad, Gujarat 380009, India";
		$address = urlencode($address);
		$url = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$geoloc = curl_exec($ch);
		$json = json_decode($geoloc);
		$lat = $json->results[0]->geometry->location->lat;
		$lon = $json->results[0]->geometry->location->lng;
		$latlonstr = $lat.'|'.$lon;
		echo $latlonstr;exit;
	}

	function GetCityFromManualAddress(){
		$val = $this->input->post('address');
		$val = urlencode($val);
		$url = "http://maps.google.com/maps/api/geocode/json?address=$val&sensor=false";
	
		$result = file_get_contents("$url");
		$json = json_decode($result);

		foreach ($json->results as $result) {
			foreach($result->address_components as $addressPart) {
			  if((in_array('locality', $addressPart->types)) && (in_array('political', $addressPart->types)))
			  $city = $addressPart->long_name;
			}
		}
		$city = mysql_real_escape_string($city);

		echo ' City : '.$city;exit;
	}

	function GetLatLongFromAddress($address){
		$address = urlencode($address);

		// Google Keys
		$randarr = array('AIzaSyD3y465iAxiPKDV9UdMGKPpJ5bFU7-FSRI','AIzaSyAgqpPxh0o9MlzCCGuB37aF8Eq8lO2LQSU','AIzaSyDrRdwYoebtJiwV3kt_WdocewktXyEq6dE','AIzaSyDAynSVP_VZaLEmVTQ__0U1zefuQuyHiV8','AIzaSyBe4Eq8REk12MaOhoPzHtf5FcqHDgjK4AY','AIzaSyAYDRHJANc38AqZUpF5_mUB2EFvWPLOAE0','AIzaSyCkF3Z9dsfKOgUsWzNJWmrS_B-7-VvqK5o','AIzaSyCyCakXahv0DtjDMwK-mcVk8MEWwzqoSOg');
		shuffle($randarr);
		$k = array_rand($randarr);

		$key = $randarr[$k];

		$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false&key='.$key;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$geoloc = curl_exec($ch);
		$json = json_decode($geoloc);

		$lat = $json->results[0]->geometry->location->lat;
		$lon = $json->results[0]->geometry->location->lng;
		$latlonstr = $lat.'|'.$lon;
		return $latlonstr;
	}

	function GetLatitudeLongitudeFromAddress(){
		if($_REQUEST['source_address'] && $_REQUEST['destination_address']){
			$source_address = $_REQUEST['source_address'];
			$destination_address = $_REQUEST['destination_address'];
			$srcaddlatlonarr = $this->GetLatLongFromAddress($source_address);
			$destaddlatlonarr = $this->GetLatLongFromAddress($destination_address);
			
			$srcaddlatlonarr = explode('|', $srcaddlatlonarr);
			$destaddlatlonarr = explode('|', $destaddlatlonarr);
			$finalarr = array();
			$finalarr['vPickupLocation_Latitude'] = $srcaddlatlonarr[0];
			$finalarr['vPickupLocation_Longitude'] = $srcaddlatlonarr[1];
			$finalarr['vDestinationLocation_Latitude'] = $destaddlatlonarr[0];
			$finalarr['vDestinationLocation_Longitude'] = $destaddlatlonarr[1];
			$data['data'] = $finalarr;
			$data['msg'] = "Success";
		}
		else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;   
	}

	function validate_login(){
		$vEmail = $this->input->post('vEmail');
		$vPassword = encrypt($this->input->post('vPassword'));
		$role=$this->input->post('role');
		if($role=='driver'){
			$this->driverLogin($vEmail,$vPassword);
		}
		else if($role=='rider'){
			$this->riderLogin($vEmail,$vPassword,$this->input->post('latitude'),$this->input->post('longitude'));
		}
		else if($role=='owner'){
			$this->vehicalOwnerLogin($vEmail,$vPassword);      
		}
		else{   
			$Data['msg'] = "Role Is Not Valid";
			header('Content-type: application/json');
			$callback = '';
			if (isset($_REQUEST['callback'])){
				$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
			}
			$main = json_encode($Data);
			echo $callback . ''.$main.'';
			exit;
		}   
	}

	function encrypt_text($value){
	   if(!$value) return false;
	   $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, 'SECURE_STRING_1', $value, MCRYPT_MODE_ECB, 'SECURE_STRING_2');
	   return trim(base64_encode($crypttext));
	}

	function updatePayment(){
		$record['iClientId']=$this->input->post('iClientId');
		$record['iCardTypeId']=$this->input->post('iCardTypeId');

		$creditcardnum = $this->input->post('vCreditcardNo');
		$creditcardnum = str_replace(' ', '', $creditcardnum);
		$record['vCreditcardNo']=$this->encrypt_text($creditcardnum);
		$record['iMonth']=$this->input->post('iMonth');
		$record['iYear']=$this->input->post('iYear');
		$record['iCvvNo']=$this->input->post('iCvvNo');
		if($this->input->post('vCreditCardHolderName')){
			$record['vCreditCardHolderName']=$this->input->post('vCreditCardHolderName');
		}

		if($record['iClientId'] && $record['iCardTypeId'] && $record['vCreditcardNo'] && $record['iMonth']  && $record['iYear'] && $record['iCvvNo']){
			$data1=$this->webservices_model->checkcard($record['iClientId']);
			if($data1>0){
				$this->webservices_model->updatetranc($record);

				$this->webservices_model->updatecardholdername($record['vCreditCardHolderName'],$record['iClientId']);

				$Data['msg'] = "Success";

				header('Content-type: application/json');
				$callback = '';
				if (isset($_REQUEST['callback'])){
					$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
				}
				$main = json_encode($Data);
				echo $callback . ''.$main.'';
				//return $callback . ''.$main.'';
				exit;
			}else{

				$this->webservices_model->updatecardholdername($record['vCreditCardHolderName'],$record['iClientId']);

				$record['ePrimmary'] = 'Yes';
				$payment = $this->webservices_model->addtranc($record);
				if ($payment) {

					$Data['msg'] = "Success";

					header('Content-type: application/json');
					$callback = '';
					if (isset($_REQUEST['callback'])){
						$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
					}
					$main = json_encode($Data);
					echo $callback . ''.$main.'';
					//return $callback . ''.$main.'';
					exit;   
				}
			}   
		}else{
			$Data['msg'] = "Failure";
			header('Content-type: application/json');
			$callback = '';
			if (isset($_REQUEST['callback'])){
				$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
			}
			$main = json_encode($Data);
			echo $callback . ''.$main.'';
			//return $callback . ''.$main.'';
			exit; 
		}
	}

	function carDetails(){
		$Lat=$_REQUEST['latitude'];
		$Lng=$_REQUEST['longitude'];
		
		$geocode=file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng='.$Lat.','.$Lng.'&sensor=false'); //call the google API V3
		$output= json_decode($geocode);
		/*echo "<pre>";print_r($output);exit;*/
		for($j=0;$j<count($output->results[0]->address_components);$j++){
			echo '<b>'.$output->results[0]->address_components[$j]->types[0].': </b>  '.$output->results[0]->address_components[$j]->long_name.'<br/>';
			if($output->results[0]->address_components[$j]->types[0]=='locality' && $output->results[0]->address_components[$j]->long_name !=''){
				if($cityNames == ''){
					$cityNames=$output->results[0]->address_components[$j]->long_name;  
				}                   
			}elseif($output->results[0]->address_components[$j]->types[0]=='administrative_area_level_1'){
				if($cityNames == ''){
					$cityNames=$output->results[0]->address_components[$j]->long_name;
				}
			}
		}exit;
		$checkCityNameinDB=$this->webservices_model->getCityId($cityNames);
		if($checkCityNameinDB['iCityId'] !=''){
			$checkCityNameForQuote=$this->webservices_model->checkCityForQuote($checkCityNameinDB['iCityId']);
			$base_upload=$this->data['base_upload'];
			$file_path = $base_path.'uploads/credit_card/'.$getPaymentDetail['vCardImage'];
			if(count($checkCityNameForQuote) > 0 ){
				for ($i=0; $i < count($checkCityNameForQuote); $i++) {
					$checkCityNameQuote[$i]['fBaseFare'] = $checkCityNameForQuote[$i]['fBaseFare'];
					$checkCityNameQuote[$i]['MaxSize'] = $checkCityNameForQuote[$i]['vTitle'];
					$checkCityNameQuote[$i]['vCar'] = $checkCityNameForQuote[$i]['vCompany'];
					$checkCityNameQuote[$i]['iCarId'] = $checkCityNameForQuote[$i]['iVehicleCompanyId'];
					$base_path = $this->data['base_path'];
					$file_path =$base_path.'uploads/car/'.$checkCityNameForQuote[$i]['iVehicleCompanyId'];
					if (file_exists($file_path)) {
						$checkCityNameQuote[$i]['image_url'] = $base_upload.'car/'.$checkCityNameForQuote[$i]['iVehicleCompanyId'].'/'.$checkCityNameForQuote[$i]['vCarimage'];
					}else {
						$checkCityNameQuote[$i]['image_url'] = "No_image_available";    
					}
				}
				$Data['data']=$checkCityNameQuote;
				/*$Data['data']['iCityId']=$checkCityNameinDB['iCityId'];   */
				$Data['msg']="Success";
				$callback = '';
				if (isset($_REQUEST['callback'])){
					$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
				}
				$main = json_encode($Data);
				echo $callback . ''.$main.'';
			}else{
				$Data['data']=$cityNames;
				$Data['msg']="Service Not Available";
				$callback = '';

				if (isset($_REQUEST['callback'])){
					$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
				}
				$main = json_encode($Data);
				echo $callback . ''.$main.'';
			}
		}else{
			$Data['data']=$cityNames;
			$Data['data']['msg']="Service Not Available";   
			$callback = '';

			if (isset($_REQUEST['callback'])){
				$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
			}
			$main = json_encode($Data);
			echo $callback . ''.$main.'';
		}
	}

	function vehicledetailbyvehicleId(){
		$iVehicleCompanyId=$this->input->post('iVehicleCompanyId');
		if($iVehicleCompanyId){
			$vehicleModelDetails=$this->webservices_model->get_vehicle_detail_byId($iVehicleCompanyId);
			/*echo "<pre>";print_r($vehicleModelDetails);exit;*/
			$base_upload=$this->data['base_upload'];
			if(count($vehicleModelDetails)>0){
				$mainarr = array();
				for ($i=0; $i <count($vehicleModelDetails) ; $i++) { 
					$mainarr[$i]['fBaseFare'] = $vehicleModelDetails[$i]['fBaseFare'];
					$mainarr[$i]['MaxSize'] = $vehicleModelDetails[$i]['vCarType'];
					$mainarr[$i]['vCar'] = $vehicleModelDetails[$i]['vCompany'];
					$mainarr[$i]['iCarId'] = $vehicleModelDetails[$i]['iVehicleCompanyId'];
					//$mainarr[$i]['vCarimage']= $vehicleDetails[$i]['vCarimage'];
					$base_path = $this->data['base_path'];
					$file_path =$base_path.'uploads/car/'.$vehicleModelDetails[$i]['iVehicleCompanyId'];
					if (file_exists($file_path)) {
						$mainarr[$i]['image_url'] = $base_upload.'car/'.$vehicleModelDetails[$i]['iVehicleCompanyId'].'/'.$vehicleModelDetails[$i]['vCarimage'];
					}else {
						$mainarr[$i]['image_url'] = "No_image_available";   
					}
					
				}
				$Data['data'] = $mainarr; 
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "Failure";
			}   
		}else{
				$Data['msg'] = "Failure";   
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}
	
	function riderLogin_21_11_2016($vEmail,$vPassword,$latitude,$longitude){
		$base_upload=$this->data['base_upload'];
		if(($vEmail != '') && ($vPassword != '')){
			$clientemail = $this->webservices_model->check_Exist('vEmail',$vEmail,'client');
			if($clientemail){
				$clientpassword = $this->webservices_model->check_password($vEmail,$vPassword,'client');
				if($clientpassword){
					$clientstatus = $this->webservices_model->check_client_auth($vEmail,$vPassword);
					if($clientstatus){
						$auth_client_exists = $this->webservices_model->check_client_auth($vEmail,$vPassword);

						if($auth_client_exists){

							/*$checkcustomerloginstatus = $this->webservices_model->check_customer_login_logout_status($vEmail);

							if($checkcustomerloginstatus=='no'){*/
								$base_path = $this->data['base_path'];
								$file_path = $base_path.'uploads/client/'.$auth_client_exists['iClientId'].'/'.$auth_client_exists['vProfileImage'];
									
								if($auth_client_exists['eImageType']=='withurl'){
									$auth_client_exists['image_Url'] = $auth_client_exists['vProfileImage'];                    
								}
								else if($auth_client_exists['eImageType']=='withouturl'){
									if (file_exists($file_path)) {
										$auth_client_exists['image_Url'] = $base_upload.'client/'.$auth_client_exists['iClientId'].'/'.$auth_client_exists['vProfileImage'];
									}else{
										$auth_client_exists['image_Url'] = $base_upload.'plash-holder.png';
									}
								}
								else {
									$auth_client_exists['image_Url'] = $base_upload.'plash-holder.png';
								}
								
								$clientDetails['iClientId'] = $auth_client_exists['iClientId'];
								$getClientDetail = $this->webservices_model->getClientDetailbyId($clientDetails['iClientId']);
								/*echo "<pre>";print_r($getClientDetail);exit;*/
								$clientDetails['fullname'] = $auth_client_exists['vFirstName'].' '.$auth_client_exists['vLastName'];
								if ($auth_client_exists['vFirstName'] && $auth_client_exists['vLastName']) {
									$clientDetails['vFirstName'] = $auth_client_exists['vFirstName'];
									$clientDetails['vLastName'] = $auth_client_exists['vLastName'];
								}

								if(isset($auth_client_exists['tAddress'])){
									$clientDetails['tAddress'] = $auth_client_exists['tAddress'];
								}
								else {
									$clientDetails['tAddress'] = "";
								}
								
								$clientDetails['vEmail'] = $auth_client_exists['vEmail'];
								$clientDetails['iMobileNo'] = $auth_client_exists['iMobileNo'];
								$clientDetails['eStatus'] = $auth_client_exists['eStatus'];
								$clientDetails['vProfileImage'] = $auth_client_exists['vProfileImage'];
								$clientDetails['vPassword'] = $auth_client_exists['vPassword'];
								$clientDetails['image_Url'] = $auth_client_exists['image_Url'];
								$clientDetails['vPromotionCode'] = ($auth_client_exists['vPromotionCode'])?$auth_client_exists['vPromotionCode']:"";
								$clientDetails['vPostalCode'] = ($getClientDetail['vPostalCode'])?$getClientDetail['vPostalCode']:"";
								$clientDetails['iCountryId'] = ($getClientDetail['iCountryId'])?$getClientDetail['iCountryId']:"";
								$clientDetails['vCountry'] = ($getClientDetail['vCountry'])?$getClientDetail['vCountry']:"";
								$clientDetails['iStateId'] = ($getClientDetail['iStateId'])?$getClientDetail['iStateId']:"";
								$clientDetails['vState'] = ($getClientDetail['vState'])?$getClientDetail['vState']:"";
								$clientDetails['iCityId'] = ($getClientDetail['iCityId'])?$getClientDetail['iCityId']:"";
								$clientDetails['vCity'] = ($getClientDetail['vCity'])?$getClientDetail['vCity']:"";

								// Current country for United States,Canada Show only Taxi, Car Pool, shuttle else Show All
								if ($this->data['SHOWALLTYPE']=='Yes') {
									$clientDetails['CurrentCountry']="ShowAll";
								} else {
									$source_country =$this->GetCountryFromAddressOrLatLong($latitude.','.$longitude,'latlong');
									$clientDetails['CurrentCountry']=$source_country['long_name'];
								}
								/*echo "<pre>";print_r($clientDetails);exit;*/
								$Data['data'] = $clientDetails;
								$Data['msg']  = "Login Successfully";
								$Data['role'] = "rider";
							/*}
							else {
								$Data['msg'] = "Customer Already Logged In";
							}*/ // end of else  
						}else{
						  $Data['msg'] = "No Record Found";
						}
					}
					else{
						$Data['msg'] = "Your status isn't active."; 
					}
				}
				else{
					$Data['msg'] = "Your password doesn't match.";
				}
			}
			else{
				$Data['msg'] = "Your email doesn't match.";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}
	
	function promotionCode(){
		if ($_REQUEST['vCode'] && $_REQUEST['iClientId']) {

			$vCode = $_REQUEST['vCode'];
			$iClientId = $_REQUEST['iClientId'];
			$rider['vPromotionCode'] = $vCode;
			$rider['iClientId'] = $iClientId;
			
			$addCode = $this->webservices_model->update_client_detail($rider);
			if ($addCode) {
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "Error";
			}
			header('Content-type: application/json');
			$callback = '';
			if (isset($_REQUEST['callback'])){
				$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
			}
			$main = json_encode($Data);
			echo $callback . ''.$main.'';
			exit;
		}
	}

	function driverRegistration(){
		$table = "driver";
		$Data['vFirstName']=$this->input->post('vFirstName');
		$Data['vLastName']=$this->input->post('vLastName');
		$Data['iStateId']=$this->input->post('iStateId');
		$Data['vCountryMobileCode']=$this->input->post('vCountryMobileCode');
		$Data['iMobileNo']=$this->input->post('iMobileNo');
		$Data['vEmail']=$this->input->post('vEmail');
		$password = $this->input->post('vPassword');
		$Data['vPassword']=encrypt($this->input->post('vPassword'));
		$Data['vCreditcardNo']=$this->input->post('vCreditcardNo');
		$Data['vZipcode']=$this->input->post('vPostalCode');
		$Data['vPromotionCode']=$this->input->post('vPromotionCode');
		$Data['eStatus']='Inactive';
		$Data['vActivationCode']=rand_str(); 
		$Data['dRegisterDate'] = date('Y-m-d');
		$vEmail = $this->input->post('vEmail');
		if($vEmail != ''){          
			$check_driver_exists = $this->webservices_model->check_email_exists($table,$vEmail);
			$table1 = "client";
			$check_client_exists = $this->webservices_model->check_email_exists($table1,$vEmail);
			if($check_driver_exists==0 && $check_client_exists==0){
				$iDriverId = $this->webservices_model->save_data($Data,$table);
				if ($iDriverId) {
					if($_FILES['vProfileImage']['name']!=''){
						$size=array();
						$size['width']='57';
						$size['height']='57';
						$size['width2']='228';
						$size['height2']='228';
						$user['vProfileImage']=$_FILES['vProfileImage']['name'];             
						$image_uploaded =$this->do_upload_img($iDriverId,'driver','vProfileImage',$size);
						$user['vProfileImage'] = $image_uploaded ;
						$user['iDriverId'] = $iDriverId;
						$DriverId = $this->webservices_model->update_driver_detail($user);
					}
				
					// Send Email
					$siteurl=$this->config->item('base_url');
					$MailFooter = $this->data['MAIL_FOOTER']; 
					$adminEmailId= $this->data['EMAIL_ADMIN'];
					$siteName = $this->data['SITE_NAME'];
					$FirstName=ucfirst($Data['vFirstName']);
					$LastName=ucfirst($Data['vLastName']);
					$link=$siteurl.'login/confirm_email?code='.$Data['vActivationCode'];
					$name1 =$FirstName.' '.$LastName.',';
					$name=ucfirst($name1);
					$bodyArr = array("#NAME#","#PASSWORD#","#EMAIL#","#SITEURL#","#MAILFOOTER#","#SITE_NAME#","#LINK#","#FIRSTNAME#","#LASTNAME#");
					$postArr = array($name,$password,$vEmail,$siteurl,$MailFooter,$siteName,$link,$FirstName,$LastName);  
					$sendClient=$this->Send("NEW_CLIENT_REGISTER","Client",$vEmail,$bodyArr,$postArr);
					$sendAdmin=$this->Send("ADMIN_NEW_CLIENT_REGISTER","Admin",$adminEmailId,$bodyArr,$postArr);    
						
					$data['msg'] = "Registered Successfully";
					$data['iDriverId']=$iDriverId;
				}else{
					$data['msg'] = "Registeration Error";
				}  
			}else{
			  $data['msg'] = "Email Address Exist";
			}
		}else{
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit; 
	}

	function getCardType(){
		$this->load->helper('string');
		$base_url = $this->data['base_url'].'uploads/';
		$base_path = $this->data['base_path'].'uploads/';
		$cardDetails = $this->webservices_model->getCardDetails();
		
		for ($i=0; $i < count($cardDetails); $i++) { 
			if ($cardDetails[$i]['vCardImage']) {
				$imagePath = $base_path.'credit_card'.'/'.$cardDetails[$i]['vCardImage'];
				if(file_exists($imagePath)){
					$cardDetails[$i]['vCardImage']= $base_url.'credit_card/'.$cardDetails[$i]['vCardImage'];
				}else{
					$cardDetails[$i]['vCardImage'] = $base_url.'No_image_available.jpg';
				}   
			}else{
				$cardDetails[$i]['vCardImage'] = $base_url.'No_image_available.jpg';
			}
		}
		if ($cardDetails) {
			$Data['data'] = $cardDetails;
			$Data['msg'] = "Success";
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function getClientDetail(){     
		$iClientId = $this->input->get('iClientId');
		$base_url = $this->config->item('base_url');
		if($iClientId != ''){
			$clientDetail = $this->webservices_model->get_client_details($iClientId);           
			if($clientDetail['iClientId']!=''){
				if($clientDetail['vProfileImage']){
					$clientDetail['imagePath'] = $base_url.'uploads/client/'.$iClientId.'/'.$clientDetail['vProfileImage'];
				}
				else {
					$clientDetail['imagePath'] = $base_url.'uploads/plash-holder.png';              
				}
				
				$clientDetail['vPassword']= $this->decrypt($clientDetail['vPassword']);
				$Data['data']= $clientDetail;
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "No Record Found";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;   
	}

	function getDriverDetail(){
		$iDriverId = $_REQUEST['iDriverId'];

		$base_url = $this->config->item('base_url');
		if($iDriverId != ''){
			$driverDetail = $this->webservices_model->get_driver_details($iDriverId);
			$driverDetail['vDriverFullname'] = $driverDetail['vFirstName'].' '.$driverDetail['vLastName'];
			if($driverDetail){
				if($driverDetail['vProfileImage']){
					$driverDetail['imagePath'] = $base_url.'uploads/driver/'.$iDriverId.'/'.$driverDetail['vProfileImage'];
				}
				else {
					$driverDetail['imagePath'] = $base_url.'uploads/red-driver.png';
				}
				
				$Data['data'] = $driverDetail;
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "No Record Found";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;   
	}

	function getStateMaster(){
		$table = "state";
		$all_states = $this->webservices_model->get_all_records($table);
		if($all_states){
			$Data['data'] = $all_states;
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit; 
	}

	function getCityMaster(){
		$iStateId=$_REQUEST['iStateId'];
		$all_city_by_state=$this->webservices_model->get_city_by_state($iStateId);
		if($all_city_by_state){
			$Data['data'] = $all_city_by_state;
			$Data['msg'] = "Success";
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit;
	}
	
	function getVehicleMaster(){
		$table = "vehicle_companies";
		$all_vehicles = $this->webservices_model->get_all_records($table);
		if($all_vehicles){
			$Data = $all_vehicles;
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit; 
	}

	function getVehicleModelMaster(){
		$all_vehicle_models = $this->webservices_model->get_all_vehicles();
		if($all_vehicle_models){
			$Data = $all_vehicle_models;
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit; 
	}

	function getColorMaster(){
		$table = "color_master";
		$all_vehicle_models = $this->webservices_model->get_all_records($table);
		if($all_vehicle_models){
			$Data = $all_vehicle_models;
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit; 
	}

	function getConfigurations(){
		$table = "configurations";
		$all_vehicle_models = $this->webservices_model->get_all_records($table);
		if($all_vehicle_models){
			$Data = $all_vehicle_models;
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit; 
	}

	function checkDriverEmail(){
		$vEmail = $this->input->post('vEmail');
		if($vEmail != ''){
			$table = "driver";
			$check_driver_exists = $this->webservices_model->check_email_exists($table,$vEmail);
			if($check_driver_exists==0){
			  $Data['msg'] = "Success";
			}else{
			  $Data['msg'] = "Email Address Exist";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function checkRiderDriverEmail(){
		$vEmail = $this->input->post('vEmail');
		if($vEmail != ''){
			$table = "client";
			$table2 = "driver";
			$check_client_exists = $this->webservices_model->check_email_exists($table,$vEmail);

			$check_driver_exists = $this->webservices_model->check_email_exists($table2,$vEmail);
			if(($check_client_exists==0) && ($check_driver_exists==0)){
			  $Data['msg'] = "Success";
			}else{
			  $Data['msg'] = "Email Address Exist";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function updateRiderDetail(){
		// iClientId, fullname, vEmail, iMobileNo (optional), tAddress (optional), iCountryId (optional), iStateId (optional), iCityId (optional), vPostalCode (optional), vProfileImage (optional)
		$base_upload=$this->data['base_upload'];
		$base_path = $this->data['base_path'];
		$this->load->helper('string');
		if($this->input->post('fullname') && $this->input->post('vEmail') && $this->input->post('iClientId')){
			// $this->input->post('vFirstName') && $this->input->post('vLastName') &&
			$fullname = explode(" ",$this->input->post('fullname'));
			$rider['vFirstName'] = $fullname[0];
			$rider['vLastName'] = $fullname[1];
			/*$rider['vFirstName'] = $this->input->post('vFirstName');
			$rider['vLastName'] = $this->input->post('vLastName');*/
			if($this->input->post('iCountryId')){
				$rider['iCountryId']=$this->input->post('iCountryId');
			}
			if($this->input->post('iStateId')){
				$rider['iStateId']=$this->input->post('iStateId');
			}
			if($this->input->post('eGender')=='' || $this->input->post('eGender')=='Male' || $this->input->post('eGender')=='Female'){
				$rider['eGender']=$this->input->post('eGender');

			}
			if($this->input->post('iCityId')){
				$rider['iCityId']=$this->input->post('iCityId');
			}
			if($this->input->post('vCity')){
				$rider['vCity']=$this->input->post('vCity');
			}
			if($this->input->post('vPostalCode')){
				$rider['vPostalCode']=$this->input->post('vPostalCode');
			}   
			
			if ($this->input->post('iMobileNo')) {
				$rider['iMobileNo'] = $this->input->post('iMobileNo');
			}

			if ($this->input->post('tAddress')) {
				$rider['tAddress'] = $this->input->post('tAddress');
			}

			$rider['vEmail'] = $this->input->post('vEmail');
			$rider['iClientId'] = $this->input->post('iClientId');
			$vEmail = trim($rider['vEmail']);
			$check_client_exists = $this->webservices_model->check_client_email_exists("client",$vEmail,$rider['iClientId']);
			$check_rider_exists= $this->webservices_model->check_repeatemail_exist("client",$vEmail,$rider['iClientId']);
			$check_driver_exists = $this->webservices_model->check_email_exists("driver",$vEmail);
			//echo $check_client_exists;exit;
			if ($check_rider_exists == 0) {
				if($check_driver_exists==0 && $check_client_exists>0){
					if($_FILES['vProfileImage']['name']!=''){
						$deletepath = $this->data['base_path'].'uploads/client/'.$rider['iClientId'].'/*';
						$files = glob($deletepath);
						foreach($files as $file){ 
							if(is_file($file))
							unlink($file);
						}
						$size=array();
						$size['width']='50';
						$size['height']='50';
						$rider['vProfileImage'] = $this->clean($_FILES['vProfileImage']['name']);            
						$fieldname = 'vProfileImage';
						$rider['eImageType'] = 'withouturl';
						$img_uploaded_leads = $this->do_upload_user_profile_photo($rider['iClientId'],$rider['vProfileImage'],
							$fieldname);
					}
					// Update details
					$ClientId = $this->webservices_model->update_client_detail($rider);
					// Get latest details
					$getClientDetail = $this->webservices_model->getClientDetail($rider['iClientId']);
					if($getClientDetail['eImageType']=='withurl'){
						$getClientDetail['image_Url'] = $getClientDetail['vProfileImage'];
					}else if($getClientDetail['eImageType']=='withouturl'){
						if($getClientDetail['vProfileImage']){
							$getClientDetail['image_Url']= $base_upload.'client/'.$getClientDetail['iClientId'].'/'.$getClientDetail['vProfileImage'];
						}else {
							$getClientDetail['image_Url'] = $base_upload.'plash-holder.png';
						}
					}else {
						$getClientDetail['image_Url'] = $base_upload.'plash-holder.png';
					}
					$getClientDetail['vPassword']= $this->decrypt($getClientDetail['vPassword']);
					$getClientDetail['fullname'] = $getClientDetail['vFirstName'].' '.$getClientDetail['vLastName'];
					$Data['data'] = $getClientDetail;
					$Data['msg'] = "Update Success";
				}else{
					$Data['msg'] = "Failure";
				}
			}else{
				$Data['msg'] = "Email Address Exist";
			}
		}else{
			$Data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function do_upload_driver_profile_photo($iDriverId,$fullfilename,$fieldname){

		if(!is_dir('uploads/'))
		{
			@mkdir('uploads/', 0777);
		}
		
		if(!is_dir('uploads/driver/'))
		{
			@mkdir('uploads/driver/', 0777);
		}
		
		if(!is_dir('uploads/driver/'.$iDriverId))
		{
			@mkdir('uploads/driver/'.$iDriverId, 0777);
		}

		$config = array(
		  'allowed_types' => "*",
		  'upload_path' => 'uploads/driver/'.$iDriverId.'/',          
		  'file_name' => str_replace(' ','',$fullfilename),
		  'max_size'=>10760668
		);
				
		$this->upload->initialize($config);
		$this->upload->do_upload($fieldname); //do upload        
		$image_data = $this->upload->data(); //get image data

		$img_uploaded = $image_data['file_name'];
		return $img_uploaded;
	}

	function do_upload_user_profile_photo($iClientId,$fullfilename,$fieldname){

		if(!is_dir('uploads/'))
		{
			@mkdir('uploads/', 0777);
		}
		
		if(!is_dir('uploads/client/'))
		{
			@mkdir('uploads/client/', 0777);
		}
		
		if(!is_dir('uploads/client/'.$iClientId))
		{
			@mkdir('uploads/client/'.$iClientId, 0777);
		}

		$config = array(
		  'allowed_types' => "*",
		  'upload_path' => 'uploads/client/'.$iClientId.'/',          
		  'file_name' => str_replace(' ','',$fullfilename),
		  'max_size'=>10760668
		);
				
		$this->upload->initialize($config);
		$this->upload->do_upload($fieldname); //do upload        
		$image_data = $this->upload->data(); //get image data

		$img_uploaded = $image_data['file_name'];
		return $img_uploaded;
	}

	function clean($string) {
		$string = date('Ymdhis').$string;
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		return preg_replace('/[^A-Za-z0-9\-.]/', '', $string); // Removes special chars.
	}

	function deleteDriverImage($iDriverId,$iParentDriverId){
		$upload_path = $this->config->item('base_path'); 
		$crop_imag=array();
		$crop_imag['image1']='57X57_';
		$crop_imag['image2']='228X228_';
		$tableData['tablename']='driver';
		$tableData['update_field']='iDriverId';
		$tableData['image_field']='vProfileImage';
		$tableData['crop_image']=$crop_imag;
		if ($iParentDriverId == 0) {
			$tableData['folder_name']='driver';
		}else{
			$tableData['folder_name']='subdriver';
		}
		$tableData['field_id']=$iDriverId;        
		$deleteImage=$this->delete_image($tableData);  
	}

	function updateDriverCompanyDetail(){
		$company_info = $this->input->post('Dcompany_info');
		$iCompanyId = $this->input->post('iCompanyId');
		$company_info['iCompanyId'] = $iCompanyId ;
		$result = $this->webservices_model->update_driver_company_detail($company_info);
		if($result == 1){
			$Data['iDriverId'] = $company_info['iDriverId'];
			$Data['msg'] = "Update Success";
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function addDriverCompanyDetail(){
		$company_info = $this->input->post('Dcompany_info');
		$iCompanyId = $this->input->post('iCompanyId');
		if($action == 'Add'){
			$CompanyId = $this->webservices_model->add_driver_company_info($company_info);
			if($CompanyId != ''){
				$Data['iDriverId'] = $company_info['iDriverId'];
				$Data['msg'] = "Add Success";
			}else{
				$Data['msg'] = "Error";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function updateDriverLicenceDetail(){
		$user = $this->input->post('licence_info');
		$iLicenceId = $this->input->post('iLicenceId');
		$user['iLicenceId'] = $iLicenceId ;
		$driverDetail = $this->input->post('driverDetail');
		$iDriverId = $driverDetail['iDriverId'];
		$iParentDriverId = $driverDetail['iParentDriverId'];
		
		if($_FILES['vDocument']['name']!=''){
			$size=array();
			$user['vDocument']=$_FILES['vDocument']['name'];             
			$image_uploaded =$this->do_upload_img($iDriverId,'driver','vDocument',$size);
			$user['vDocument'] = $image_uploaded ;
		}

		$result = $this->webservices_model->update_driver_licence_detail($user);
		if($result == 1){
			$Data['iDriverId'] = $iDriverId;
			$Data['msg'] = "Update Success";
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function addDriverLicenceDetail(){
		$user = $this->input->post('licence_info');
		$iLicenceId = $this->input->post('iLicenceId');
		$user['iLicenceId'] = $iLicenceId ;
		$driverDetail = $this->input->post('driverDetail');
		$iDriverId = $driverDetail['iDriverId'];
		$iParentDriverId = $driverDetail['iParentDriverId'];
		if($_FILES['vDocument']['name']!=''){
			$size=array();
			$user['vDocument']=$_FILES['vDocument']['name'];             
			$image_uploaded =$this->do_upload_img($iDriverId,'driver','vDocument',$size);
			$user['vDocument'] = $image_uploaded ;
		}
		$LicenceId = $this->webservices_model->add_driver_licence_info($company_info);
		if($LicenceId != ''){
			$Data['iDriverId'] = $company_info['iDriverId'];
			$Data['msg'] = "Add Success";
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function deletedocument(){
		$upload_path = $this->data['base_upload']; 
		$iDriverId  = $this->input->get('iDriverId');
		$iLicenceId  = $this->input->get('iLicenceId');
		// echo "Driver ID : ".$iDriverId.', Licence Id : '.$iLicenceId;exit();
		$deleteDocument=$this->delete_licence_document($tableData);  
		redirect($this->data['admin_url'].'driver/licence_info/'.$iDriverId);
	}

	function addDriverVehicleDetail(){
		$vehicleDetail = $this->input->post('vehicleDetail');
		// echo "<pre>";print_r($vehicleDetail);exit();
		$table = "drivers_vehicle_information";
		$iVehicleInformationId = $this->webservices_model->save_data($vehicleDetail,$table);
		if($vehicleDetail['iVehicleInformationId'] != ''){
			$Data['iDriverId'] = $vehicleDetail['iDriverId'];
			$Data['msg'] = "Insert Success";
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function GetStatesByCountry(){
		$iCountryId = $this->input->post('iCountryId');
		if($iCountryId != ''){
			$states = $this->webservices_model->get_allstates_by_country($iCountryId);          
			if($states){
				$Data['data'] = $states;
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "No Record Found";
			}
		}else{
			$Data['msg'] = "Country ID Is Blank";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;   
	}

	function get_cities(){
		$iCountryId = $this->input->get('iCountryId');
		$iStateId = $this->input->get('iStateId');
		if($iCountryId != '' && $iStateId!=''){
			$city = $this->webservices_model->get_all_cities($iCountryId,$iStateId);            
			if($city[0]['iCityId']!=''){
				$Data = $city;
			}else{
				$Data['0']['msg'] = "No Record Found";
			}
		}else{
			$Data['0']['msg'] = "CountryId or StateId Is Blank.";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;   
	}

	function forgot_password_17_11_2016(){
		$email=$this->input->post('vEmail');
		$role=$this->input->post('role');
		if($role=='driver'){
			$getDriverDetail=$this->webservices_model->checkdriver_mail($email);
			if(count($getDriverDetail)>0){
				$records=$getDriverDetail;
				$my_base_url = $this->data['base_url'];
				$data1['vForgotPasswordString']=rand_str();  
				$this->webservices_model->updatetablestatus($records['vEmail'],'driver',$data1);
				$records['vPassword']=$this->decrypt($records['vPassword']);
				$name=$records['vFirstName'].' '.$records['vLastName'];
				$siteurl=$this->config->item('base_url');
				$MailFooter = $this->data['MAIL_FOOTER']; 
				$siteName = $this->data['SITE_NAME'];
				$link=$siteurl.'forget-password?code='.$data1['vForgotPasswordString'].'&role=driver';
				//$link=$siteurl.'login/reset-password/'.$data1['vForgotPasswordString']; 
				//http://192.168.1.41/php/ride_in/change-password-client
				$bodyArr = array("#NAME#","#PASSWORD#","#EMAIL#","#SITEURL#","#MAILFOOTER#","#SITE_NAME#","#LINK#","#IMAGE_URL#");
				$postArr = array($name,$records['vPassword'],$records['vEmail'],$siteurl,$MailFooter,$siteName,$link,$my_base_url); 
				$sendClient=$this->Send("RESET_PASSWORD","Client",$records['vEmail'],$bodyArr,$postArr);
				$Data['msg'] = "Email Send Success";
			}else{
				$Data['msg'] = "Email Address Not Exist";       
			}
		}else if($role=='rider'){
			$getClientDetail=$this->webservices_model->checkuser_mail($email);
			if(count($getClientDetail)>0){
				$data1['vForgotPasswordString']=rand_str();       
				$records=$getClientDetail;
				$this->webservices_model->updatetablestatus($records['vEmail'],'client',$data1);
				$records['vPassword']=$this->decrypt($records['vPassword']);
				$name=$records['vFirstName'].' '.$records['vLastName'];
				$MailFooter = $this->data['MAIL_FOOTER']; 
				$siteName = $this->data['SITE_NAME'];
				$siteurl=$this->config->item('base_url');
				$link=$siteurl.'forget-password?code='.$data1['vForgotPasswordString'].'&role=rider'; 
				//http://192.168.1.41/php/ride_in/change-password-client
				$my_base_url = $this->data['base_url'];
				$bodyArr = array("#NAME#","#PASSWORD#","#EMAIL#","#SITEURL#","#MAILFOOTER#","#SITE_NAME#","#LINK#","#IMAGE_URL#");
				$postArr = array($name,$records['vPassword'],$records['vEmail'],$siteurl,$MailFooter,$siteName,$link,$my_base_url); 
				$sendClient=$this->Send("RESET_PASSWORD","Client",$records['vEmail'],$bodyArr,$postArr);
				$Data['msg'] = "Email Send Success";
			}else{
				$Data['msg'] = "Email Address Not Exist";
			}
		}else if($role=='owner'){
			// echo "1 \n";
			$getOwnerDetail=$this->webservices_model->check_owner_mail($email);
			if(count($getOwnerDetail)>0){
				// echo "2 \n";
				$data1['vForgotPasswordString']=rand_str();
				$this->webservices_model->updatetablestatus($getOwnerDetail['vEmail'],'vehicle_owner',$data1);
				$getOwnerDetail['vPassword']=$this->decrypt($getOwnerDetail['vPassword']);
				$name=$getOwnerDetail['vFirstName'].' '.$getOwnerDetail['vLastName'];
				$MailFooter = $this->data['MAIL_FOOTER']; 
				$siteName = $this->data['SITE_NAME'];
				$siteurl=$this->config->item('base_url');
				$link=$siteurl.'forget-password?code='.$data1['vForgotPasswordString'].'&role=vehicle_owner'; 
				//http://192.168.1.41/php/ride_in/change-password-client
				$my_base_url = $this->data['base_url'];
				$bodyArr = array("#NAME#","#PASSWORD#","#EMAIL#","#SITEURL#","#MAILFOOTER#","#SITE_NAME#","#LINK#","#IMAGE_URL#");
				$postArr = array($name,$getOwnerDetail['vPassword'],$getOwnerDetail['vEmail'],$siteurl,$MailFooter,$siteName,$link,$my_base_url); 
				$sendClient=$this->Send("RESET_PASSWORD","Client",$getOwnerDetail['vEmail'],$bodyArr,$postArr);
				$Data['msg'] = "Email Send Success";
				// echo "3 \n";
			}else{
				// echo "4 \n";
				$Data['msg'] = "Email Address Not Exist";
			}
			// echo "5 \n";
		}else{
			// echo "6 \n";
			$Data['msg'] = "Role Is Not Valid";
		}
		// exit;
		
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit; 
	}

	function get_free_ride(){
		$iClientId = $_REQUEST['iClientId'];
		if($iClientId != ''){
			$clientDetail = $this->webservices_model->getClientPromoCode($iClientId);
			if($clientDetail['iClientId'] == ''){
				$Data['msg'] = "No Record Found";
			}else{
				$share['Message']='Onetouchcab! Join to our Onetouchcab';   
				$share['vPromotionCode'] = $clientDetail['vPromotionCode'];
				$share['Link']=$this->data['base_url'].'invite/'.$clientDetail['vPromotionCode'];
				$Data['data'] = $share;
				$Data['msg'] = 'Success';
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	// For get available Choices of car type
	function availableChoices(){
		$iCityId=$this->input->get('iCityId');
		echo $iCityId;exit;
		$checkCityExist = $this->webservices_model->check_Exist('iCityId',$iCityId,'city');
		if ($checkCityExist==0) {
			$Data['msg'] = "City Not Available";    
		}
		else{
			// get all drivers of given city.
			$driverInformation=$this->common_model->getDriverByCity($iCityId); 
			if (count($driverInformation)>0) {
				$availebleCarTyes=array();
				// check available vehicles in specific city
				foreach($driverInformation as $i=>$value){
					$checkDriversVechileDetail=$this->common_model->checkVehicleDetail($value['iDriverId']);
					if(count($checkDriversVechileDetail)>0){
						foreach($checkDriversVechileDetail as $j=>$val){
							if(! in_array($val['iCarTypeId'],$availebleCarTyes)){
								$availebleCarTyes[]=$val['iCarTypeId'];
								// echo "<pre>";print_r($val['iCarTypeId']);exit();
							}                    
						}  
					}                   
				}
				$availbleTypes=$this->common_model->getAvailableCartypes($availebleCarTyes);
				if(count($availbleTypes)>0){
					foreach($availbleTypes as $key=>$val){
						$image = $this->data['base_upload'].'car_type/'.$val['iCarTypeId'].'/'.$val['vCarImage'];
						if(file_exists($image)){
							$availbleTypes[$key]['Image_Url']='Image Not Found.';
						}else{ 
							$availbleTypes[$key]['Image_Url']= $image;
							
						}
					}
					$Data['availableCarTypes']=$availbleTypes;
				}else{
					$Data['msg'] = "Car Type Not Available";
				}
			}else{
				$Data['msg'] = "Driver Not Available";
			}
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;   
	}

	function getDriversByCarType(){
		$iCityId=$this->input->get('iCityId');              
		$iCarTypeId=$this->input->get('iCarTypeId'); 
		$driverInformation=$this->common_model->getDriverByCity($iCityId);
		$finaldriver=array();

		foreach($driverInformation as $i=>$value){
			$checkDriversVechileDetails=$this->common_model->vechicles_by_driver_city($value['iDriverId'],$iCarTypeId);
			if(count($checkDriversVechileDetails)>0){
				$finaldriver[]=$checkDriversVechileDetails['iDriverId'];
			}
		}
		foreach($finaldriver as $i=>$value){
			$iDriverId=$value;
			$Driverdetail[]=$this->webservices_model->getdriverdetail($iDriverId);
		}
		if(count($Driverdetail)>0){
			foreach($Driverdetail as $key=>$val){
				if($val['vProfileImage']!=''){
					if ($val['iParentDriverId']==0) {
						$image = $this->data['base_path'].'uploads/driver/'.$val['iDriverId'].'/'.$val['vProfileImage'];
					}else{
						$image = $this->data['base_path'].'uploads/subdriver/'.$val['iDriverId'].'/'.$val['vProfileImage'];
					}
					if(file_exists($image)){
						if ($val['iParentDriverId']==0) {
							$Driverdetail[$key]['ImageURL']= $this->data['base_url'].'uploads/driver/'.$val['iDriverId'].'/57X57_'.$val['vProfileImage'];
						}else{
							$Driverdetail[$key]['ImageURL']= $this->data['base_url'].'uploads/subdriver/'.$val['iDriverId'].'/57X57_'.$val['vProfileImage'];
						}
					}else{ 
						$Driverdetail[$key]['ImageURL']='Image Not Found';
					}
				}else{ 
					$Driverdetail[$key]['vProfileImage']='Image Not Found';
					$Driverdetail[$key]['ImageURL']='Image Not Found';
				}       
			}
			$Data = $Driverdetail;
		}else{
			$Data['0']['msg'] = 'No Driver Available For This Option';
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;        
	}

	function checkEmail(){
		$vEmail = $this->input->get('vEmail');
		if($vEmail != ''){
			$check_client_exists = $this->webservices_model->check_email_exists('client',$vEmail);
			$check_driver_exists = $this->webservices_model->check_email_exists('driver',$vEmail);
			if($check_driver_exists==0 && $check_client_exists==0){
				$Data['0']['msg'] = "Email Address Not Exist";  
			}else{
				$Data['0']['msg'] = "Email Address Exist";
			}
		}else{
				$Data['0']['msg'] = "Please Enter Email Address";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function GetAllCarByFixRide(){
		if($_REQUEST['iRideId']){
			$iRideId = $_REQUEST['iRideId'];
			$getAllCarType=$this->webservices_model->allCarType();
			$mainarr = array();

			foreach ($getAllCarType as $key => $value) {
				$ratesByCarType=$this->webservices_model->rateByCar($iRideId,$value['iVehicleCompanyId']);
				$mainarr[$key]['iRateId']=$ratesByCarType['iRateId'];
				$mainarr[$key]['fTotalPrice']= $this->data['CURRENCY'].number_format($ratesByCarType['fTotalPrice'],2);
				$image = $this->data['base_upload'].'car/'.$value['iVehicleCompanyId'];
				if(file_exists($image)){
					$mainarr[$key]['Image_Url']='Image Not Found';
				}else{ 
					$mainarr[$key]['Image_Url']= $this->data['base_url'].'uploads/car/'.$value['iVehicleCompanyId'].'/'.$value['vCarimage'];
				}
			}
			$data['data'] = $mainarr;    
			$data['msg'] = 'Success';    
		}
		else {
			$data['msg'] = 'Failure';       
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function getFixRide(){
		$iCityId = $_REQUEST['iCityId'];
		if($iCityId != ''){
			$getAllCarType=$this->webservices_model->allCarType();                        
			$getFixRide=$this->webservices_model->allFixRide($iCityId);
			if(count($getFixRide)>0){
				foreach ($getFixRide as $i => $value) {
					/*$startlatlong = $this->GetLatLongFromAddress(mysql_real_escape_string($value['vPickUpLocation']));
					$startlatlong = explode('|', $startlatlong);
					$finishlatlong = $this->GetLatLongFromAddress(mysql_real_escape_string($value['vDropLocation']));
					$finishlatlong = explode('|', $finishlatlong);
					$getFixRide[$i]['vPickupLocation_Latitude'] = $startlatlong[0];
					$getFixRide[$i]['vPickupLocation_Longitude'] = $startlatlong[1];

					$getFixRide[$i]['vDestinationLocation_Latitude'] = $finishlatlong[0];
					$getFixRide[$i]['vDestinationLocation_Longitude'] = $finishlatlong[1];*/

					$getFixRide[$i]['RatesByCar']=$getAllCarType;                
					foreach ($getFixRide[$i]['RatesByCar'] as $j => $data) {                    
						$ratesByCarType=$this->webservices_model->rateByCar($value['iRideId'],$data['iVehicleCompanyId']);
						$getFixRide[$i]['RatesByCar'][$j]['iRateId']=$ratesByCarType['iRateId'];
						$getFixRide[$i]['RatesByCar'][$j]['fTotalPrice']= $this->data['CURRENCY'].number_format($ratesByCarType['fTotalPrice'],2);
						$image = $this->data['base_upload'].'car/'.$getFixRide[$i]['RatesByCar'][$j]['iVehicleCompanyId'];
						if(file_exists($image)){
							$getFixRide[$i]['RatesByCar'][$j]['Image_Url']='Image Not Found.';
						}else{ 
							$getFixRide[$i]['RatesByCar'][$j]['Image_Url']= $this->data['base_url'].'uploads/car/'.$getFixRide[$i]['RatesByCar'][$j]['iVehicleCompanyId'].'/'.$getFixRide[$i]['RatesByCar'][$j]['vCarimage'];
						}
					}            
				}
				$Data['data']=$getFixRide;
				$Data['msg'] = "Success";

			}else{
				$Data['msg'] = "No Fix Ride Available For This City";
			}
		}else{
			$Data['msg'] = "Please Enter City";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function cardTypes(){
		$card_types = $this->webservices_model->get_card_types();
		if(count($card_types)>0){
			$Data=$card_types;
		}else{
			$Data['0']['msg'] = "No Record Found";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function changePassword(){
		$iDriverId = $this->input->post('iDriverId');
		$iClientId = $this->input->post('iClientId');
		$iVehicleOwnerId = $this->input->post('iVehicleOwnerId');
		$oldpass = encrypt($this->input->post('vOldpass'));
		$pass=$this->webservices_model->getpasswordclient($iClientId,$oldpass);
		$pass1=$this->webservices_model->getpassworddriver($iDriverId,$oldpass);
		$pass2=$this->webservices_model->getpasswordowner($iVehicleOwnerId,$oldpass);
		if(count($pass)>0)
		{
			$old=1;
		}
		if(count($pass1)>0)
		{
			$old=1; 
		}
		if(count($pass2)>0)
		{
			$old=1; 
		}

		if($old==1){
			$newpasswrd=$this->input->post('vPassword');        
			$user['vPassword']=encrypt($this->input->post('vPassword'));
			
			if($iDriverId != ''){
				$user['iDriverId'] = $iDriverId ;
				$result = $this->webservices_model->update_driver_detail($user);
				$Data['msg'] = "Password Update Success";
			}elseif($iClientId!=''){
				$user['iClientId'] = $iClientId;
				$result = $this->webservices_model->update_client_detail($user);
				$Data['msg'] = "Password Update Success";
			}elseif($iVehicleOwnerId!=''){
				$result = $this->webservices_model->update_owner_detail($user,$iVehicleOwnerId);
				$Data['msg'] = "Password Update Success";
			}else{
				$Data['msg'] = "Error";
			}
		}else{   
			$Data['msg'] = "Old Password Not Found";
		}
		
		
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}

		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function get_client_detail_for_payment(){ 
		$iClientId = $_REQUEST['iClientId'];

		if($iClientId != ''){
			$clientDetail = $this->webservices_model->get_client_detail_for_pay($iClientId);
			echo "<pre>";print_r($clientDetail);exit;
			if($clientDetail['vCreditcardNo']!=''){
				$clientDetail['line1']=$clientDetail['vCity'].', '.$clientDetail['vStatecode'].', '.$clientDetail['vPostalCode'];
				$Data[]= $clientDetail;
			}else{
				$Data['0']['msg'] = "No Record Found";
			}
		}else{
			$Data['0']['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function checkCity(){
		$iStateId=$this->input->get('iStateId');    
		$iCountryId=$this->input->get('iCountryId');
		$vCity=ucwords($this->input->get('vCityName'));
		$checkCityExist = $this->webservices_model->city_exists($iStateId,$iCountryId,$vCity);
		if ($checkCityExist==0) {
			$Data['0']['msg'] = "City Not Exist";   
		}else{
			$Data['0']['msg'] = "City Exist";   
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;   
	}

	function getCityName(){     
		$Lat=$_REQUEST['latitude'];
		$Lng=$_REQUEST['longitude'];
		
		$geocode=file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng='.$Lat.','.$Lng.'&sensor=false'); //call the google API V3
		$output= json_decode($geocode);
		
		for($j=0;$j<count($output->results[0]->address_components);$j++){
			/*echo '<b>'.$output->results[0]->address_components[$j]->types[0].': </b>  '.$output->results[0]->address_components[$j]->long_name.'<br/>';*/
			if($output->results[0]->address_components[$j]->types[0]=='locality' && $output->results[0]->address_components[$j]->long_name !=''){
				if($cityNames == ''){
					$cityNames=$output->results[0]->address_components[$j]->long_name;  
				}                   
			}elseif($output->results[0]->address_components[$j]->types[0]=='administrative_area_level_1'){
				if($cityNames == ''){
					$cityNames=$output->results[0]->address_components[$j]->long_name;
				}
			}
		}
		

		$cityNamesfilter = mysql_real_escape_string($cityNames);
		
		$checkCityNameinDB=$this->common_model->getCityId($cityNamesfilter);
		/*echo '<pre>';
		print_r($checkCityNameinDB);exit;*/
		if($checkCityNameinDB['iCityId'] !=''){     

			$checkCityNameForQuote=$this->common_model->checkCityForQuote($checkCityNameinDB['iCityId']);
			if(count($checkCityNameForQuote) > 0 ){
				$Data['data']['City']=$checkCityNameinDB['vCity'];  
				$Data['data']['iCityId']=$checkCityNameinDB['iCityId']; 
				$Data['msg']="Service Available";   
				$callback = '';
				if (isset($_REQUEST['callback'])){
					$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
				}
			
				$main = json_encode($Data);
				echo $callback . ''.$main.'';
			}else{
				$Data['data']['City']=$cityNamesfilter;
				$Data['msg']="Service Not Available";
				$callback = '';

				if (isset($_REQUEST['callback'])){
					$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
				}
			
				$main = json_encode($Data);
				echo $callback . ''.$main.'';
			}
		}else{
			$Data['data']['City']=$cityNamesfilter;
			$Data['msg']="Service Not Available";   
			$callback = '';

			if (isset($_REQUEST['callback'])){
				$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
			}
	
			$main = json_encode($Data);
			echo $callback . ''.$main.'';
		}
	}

	function updatCreditCard(){
		$iClientId=$this->input->get('iClientId');
		$iCardTypeId=$this->input->get('iCardTypeId');
		$vCreditcardNo=$this->input->get('vCreditcardNo');    
		$iMonth=$this->input->get('iMonth');
		$iYear=$this->input->get('iYear');
		$iCvvNo=$this->input->get('iCvvNo');
		
		$client['iClientId']=$iClientId;
		$client['iCardTypeId']=$iCardTypeId;
		$client['vCreditcardNo']=$vCreditcardNo;
		$client['iMonth']=$iMonth;
		$client['iYear']=$iYear;    
		$client['iCvvNo']=$iCvvNo;    
		

		$ClientId = $this->webservices_model->update_client_creditcard_detail($client);
  
		if($ClientId > 0 ){             
			$Data['msg']="Sucess";  
		}else{
			$Data['msg']="Failed";  
		}

		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';       
	}

	function getridelist(){
		$iDriverId=$this->input->get('iDriverId');
		$iCityId=$this->input->get('iCityId');
		
		$checkDriverExist = $this->webservices_model->check_Exist('iDriverId',$iDriverId,'driver');
		$checkCityExist = $this->webservices_model->check_Exist('iCityId',$iCityId,'city');
		
		if ($checkCityExist > 0) {  
			if($checkDriverExist > 0){
				$ridelist=$this->webservices_model->getridelist($iCityId);
				$final_array=array();
				//echo "<pre>";print_r($ridelist);
				for($i=0;$i<count($ridelist);$i++){
					$newdriver=explode(",",$ridelist[$i]['iRideDriverId']);     
					if(in_array($iDriverId,$newdriver)){
						$final_array[]=$ridelist[$i];
					}
				}
				$final_array = array_unique($final_array);
				//echo "<pre>";print_r($final_array);
				if(count($final_array)> 0)
				{
					$Data= $final_array;
				}
				else{
					$Data['0']['msg']="No Record Found";    
				}
			}
			else{
					$Data['msg'] = "Driver Is Not Found";
				}
		}
		else{
					$Data['msg'] = "City Is Not Found";
			}

		
		
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.''; 
	}

	function getfare(){
		$iCarTypeId=$this->input->get('iCarTypeId');
		$iRideId=$this->input->get('iRideId');

		$checkCarExist = $this->webservices_model->check_Exist('iCarTypeId',$iCarTypeId,'car_type');
		$checkRideExist = $this->webservices_model->check_Exist('iRideId',$iRideId,'fix_ride');

		if ($checkCarExist > 0) {   
			if($checkRideExist > 0){
				$totalfare=$this->webservices_model->gettotalfare($iCarTypeId,$iRideId);
				if(count($totalfare)> 0)
				{
					$Data= $totalfare;
				}
				else{
					$Data['msg']="No Record Found"; 
				}
			}
			else{
				$Data['msg'] = "Ride Is Not Found";
			}
		}
		else{
				$Data['msg'] = "Car Type Not Available";
			}

		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.''; 
	}

	function getDriverTriplist(){
		$iDriverId=$this->input->get('iDriverId');
		$trips_detail = $this->webservices_model->getAllTripDetails($iDriverId);
		
		for($i=0;$i<count($trips_detail);$i++){
			$d=$trips_detail[$i]['dTripDate'];
			$nd=date('dS F Y h:s:i',strtotime($d));
			$trips_detail[$i]['dTripDate'] =$nd;
		}
		//echo "<pre>";print_r($trips_detail);exit;
		if(count($trips_detail) > 0){
			//$Data=$trips_detail;
			$Data['trip detail']=$trips_detail;
			// echo "<pre>";print_r($Data);exit();
			$Data['msg']="Success";
		}
		else{
			$Data['0']['msg']="No Record Found";    
		}
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
	}

	function FareSplit(){
		if($_REQUEST['contact_string'] && $_REQUEST['iTripId'] && $_REQUEST['iClientId']){
	
			$contactarr = explode('||', $_REQUEST['contact_string']);

			/*echo '<pre>';
			print_r(count($contactarr));exit;*/

			$iTripId = $_REQUEST['iTripId'];
			$iClientId = $_REQUEST['iClientId'];

			$client_details = $this->webservices_model->get_client_personal_information($iClientId);

			$trip_details = $this->webservices_model->get_trip_information($iTripId);

			if($client_details){
				if($trip_details){
					for($i=0;$i<count($contactarr);$i++){
						$user_data = $this->webservices_model->get_user_and_device_details($contactarr[$i]);
						if($user_data){
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['vDeviceid'] = $user_data['device_id'];
							$pushNotificationData['msg'] = $client_details['vFirstName']." ".$client_details['vLastName']." sent invitation for ride from ".$trip_details['vPickupLocation']." to ".$trip_details['vDestinationLocation']." ### ".$iTripId;
							$pushNotificationData['eUserType'] = "Rider";
							$properdata=$this->pushNotification($pushNotificationData);

							$status = $this->webservices_model->check_trip_partner_exists_with_declined_status($iTripId,$user_data['iClientId'],$iClientId);
							if($status!='exist'){
								$partnerarr['iTripId'] = $iTripId;
								$partnerarr['iClientId'] = $iClientId;
								$partnerarr['iPartnerId'] = $user_data['iClientId'];
								$partnerarr['eRequestStatus'] = 'Declined';

								// check record exist
								$part_exist_status = $this->webservices_model->check_trip_partner_exists_fare($iTripId,$iClientId,$user_data['iClientId']);
								// end of code for record
								if($part_exist_status=='no'){
									$totalrows = $this->webservices_model->save_trip_partner_details($partnerarr);
								}
							}
						}
					}
					$data['msg']="Success";         
				}
				else {
					$data['msg']="Trip Not Exist";      
				}
			}
			else {
				$data['msg']="Rider Not Exist";     
			}
		}
		else {
			$data['msg']="Failure"; 
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit;
	}

	function FareSplitResponse(){   
		if($_REQUEST['iTripId'] && $_REQUEST['iPartnerId']){
			
			$iTripId = $_REQUEST['iTripId'];
			$iPartnerId = $_REQUEST['iPartnerId'];
			$trip_data = $this->webservices_model->get_single_trip_details($iTripId);
			$iClientId = $trip_data['iClientId'];
			$status = $this->webservices_model->check_trip_partner_exists_with_declined_status($iTripId,$iPartnerId,$iClientId);

			if($status=='exist'){
				$res = $this->webservices_model->update_fare_split_response_for_trip_partner($iTripId,$iPartnerId,$iClientId);
				$user_data = $this->webservices_model->get_user_info_and_device_details($iClientId);
				$partner_data = $this->webservices_model->get_partner_details($iPartnerId);
				if($partner_data && $user_data){
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['vDeviceid'] = $user_data['device_id'];
					$pushNotificationData['msg'] = $partner_data['vFirstName']." ".$partner_data['vLastName']." accepted your invitation for ride from ".$trip_data['vPickupLocation']." to ".$trip_data['vDestinationLocation']." $$$$ ".$iTripId;
					$pushNotificationData['eUserType'] = "Rider";
					$properdata=$this->pushNotification($pushNotificationData);
				}
				$data['msg']="Success";
			}
			else {
				$status = $this->webservices_model->check_trip_partner_exists($iTripId,$iPartnerId,$iClientId);
				if($status!='exist'){
					$farearr['iTripId'] = $iTripId;
					$farearr['iPartnerId'] = $iPartnerId;
					$farearr['iClientId'] = $iClientId;
					$farearr['eRequestStatus'] = 'Accepted';

					$part_exist_status = $this->webservices_model->check_trip_partner_exists_fare_response($iTripId,$iClientId,$iPartnerId);
					if($part_exist_status=='no'){
						$res = $this->webservices_model->save_fare_split_info($farearr);
					}
				}

				$user_data = $this->webservices_model->get_user_info_and_device_details($iClientId);
				$partner_data = $this->webservices_model->get_partner_details($iPartnerId);
				if($partner_data && $user_data){
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['vDeviceid'] = $user_data['device_id'];
					$pushNotificationData['msg'] = $partner_data['vFirstName']." ".$partner_data['vLastName']." accepted your invitation for ride from ".$trip_data['vPickupLocation']." to ".$trip_data['vDestinationLocation']." $$$$ ".$iTripId;
					$pushNotificationData['eUserType'] = "Rider";
					$properdata=$this->pushNotification($pushNotificationData);
				}
				$data['msg']="Success"; 
			}
		}
		else {
			$data['msg']="Failure"; 
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit;
	}

	function get_taxi_latlng(){     
		$larlng_data = $this->webservices_model->get_lating('taxi');    
		if(count($larlng_data) > 0){
			$Data[]= $larlng_data;
		}else{
			$Data['0']['msg'] = "No Record Found";
		}
		
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;   
	}

	function get_chofer_latlng(){       
		$larlng_data = $this->webservices_model->get_lating('chofer');  
		if(count($larlng_data) > 0){
			$Data[]= $larlng_data;
		}else{
			$Data['0']['msg'] = "No Record Found";
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;   
	}

	function get_elite_latlng(){        
		$larlng_data = $this->webservices_model->get_lating('elite');   
		if(count($larlng_data) > 0){
			$Data[]= $larlng_data;
		}else{
			$Data['0']['msg'] = "No Record Found";
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;   
	}

	function getBasicFare(){
		$iCityId=$_REQUEST['iCityId'];
		$iCarTypeId=$_REQUEST['iCarTypeId'];
		$checkCityExist = $this->webservices_model->check_Exist('iCityId',$iCityId,'city');
		
		$checkCarExist = $this->webservices_model->check_Exist('iCarTypeId',$iCarTypeId,'car_type');
		
		if ($checkCityExist > 0 && $checkCarExist > 0 ) {
			$farDetails=$this->webservices_model->getfaredetail($iCarTypeId,$iCityId);
			if(count($farDetails) > 0){
				$Data['data']=$farDetails;
			}else{
				$Data['msg'] = "No Detail Found For This Option";
			}
		}else{
			if ($checkCityExist <= 0) {
				$Data['msgCity'] = "City Not Available";
			}
			if ($checkCarExist <= 0) {
				$Data['msgCar'] = "Car Type Not Available";
			}
			
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;   
	}

	function adddriverridingstatus(){
		$ridingstatus['iDriverId'] = $this->input->post('iDriverId');
		$ridingstatus['iClientId'] = $this->input->post('iClientId');
		$ridingstatus['fLatitude'] = $this->input->post('fLatitude');
		$ridingstatus['fLongitude'] = $this->input->post('fLongitude');

		$checkDriverExist = $this->webservices_model->check_Exist('iDriverId',$ridingstatus['iDriverId'],'driver');
		$checkClientExist = $this->webservices_model->check_Exist('iClientId',$ridingstatus['iClientId'],'client');
		if ($checkDriverExist > 0 && $checkClientExist > 0 ) {

			$iRidingStatusId = $this->webservices_model->save_data($ridingstatus,'riding_status');
			if($iRidingStatusId != ''){
				$Data['iRidingStatusId'] = $iRidingStatusId;
				$Data['msg'] = "Add Success";
			}else{
				$Data['msg'] = "Error";
			}
		}else{
			if ($checkDriverExist <= 0) {
				$Data['msgDriver'] = "Driver Not Exist";
			}
			if ($checkClientExist <= 0) {
				$Data['msgClient'] = "Client Not Exist";
			}
			
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function getdriverridingstatus(){
		$iDriverId = $this->input->get('iDriverId');
		$iClientId = $this->input->get('iClientId');
		$ridingstatus = $this->webservices_model->get_driver_riding_status($iDriverId,$iClientId);
		$myLastElement = end($ridingstatus);
		if($myLastElement['iRidingStatusId'] != ''){
			$Data = $myLastElement;
		}else{
			$Data['msg'] = "Error";
		}
		
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function getallactivecarstype(){
		$cars = $this->webservices_model->get_all_cartypes();
		if(count($cars) > 0){
			$Data = $cars;
		}else{
			$Data['msg'] = "Error";
		}
		
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function getPaymentDetails(){
		$iClientId = $_REQUEST['iClientId'];
		$base_upload=$this->data['base_upload'];
		if ($iClientId) {
			$getPaymentDetail = $this->webservices_model->getPaymentDetailByclientId($iClientId);
			if ($getPaymentDetail) {
				$credit_card_origin_no = $this->decrypt_text($getPaymentDetail['vCreditcardNo']);
				$getPaymentDetail['original_credit_card_no'] = $credit_card_origin_no;
				$getPaymentDetail['vCreditcardNo'] = "XXXX XXXX XXXX ".substr($credit_card_origin_no,-4,4);

				$base_path = $this->data['base_path'];
				$file_path = $base_path.'uploads/credit_card/'.$getPaymentDetail['vCardImage'];
				
				if (file_exists($file_path)) {
					$getPaymentDetail['image_url'] = $base_upload.'credit_card/'.$getPaymentDetail['vCardImage'];
				}else{
					$getPaymentDetail['image_url'] = 'No_image_available';
				}
				$Data['data'] = $getPaymentDetail;
				$Data['msg'] = 'Success';
			}else{
				$Data['msg'] = "Failure";
			}
			
		}else{
			$Data['msg'] = "Failure";
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function checkPromoCode(){
		$vCode = $_REQUEST['vCode'];
		$iClientId = $_REQUEST['iClientId'];
		$checkPromoCode = $this->webservices_model->checkPromoCode($vCode,$iClientId);
		
		if ($checkPromoCode) {
			$Data['msg'] = 'Success';
		}else{
			$Data['msg'] = 'Promocode Not Valid';
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function getCardInfo(){
		$iClientId = $_REQUEST['iClientId'];
		$ePrimmary = 'Yes';
		$base_upload=$this->data['base_upload'];
		if ($iClientId && $ePrimmary) {
			$getCardInfo = $this->webservices_model->getCardInfo($iClientId,$ePrimmary);
			$base_path = $this->data['base_path'];
			$file_path = $base_path.'uploads/credit_card/'.$getCardInfo['vCardImage'];
			if ($getCardInfo) {
				for ($i=0; $i <count($getCardInfo) ; $i++) { 
					$mainarr[$i]['iCardId']= $getCardInfo[$i]['iTransactionId'];
					$mainarr[$i]['vCreditcardNo']= $getCardInfo[$i]['vCreditcardNo'];
					$mainarr[$i]['iMonth']= $getCardInfo[$i]['iMonth'];
					$mainarr[$i]['iYear']= $getCardInfo[$i]['iYear'];
					$mainarr[$i]['vCardName']= $getCardInfo[$i]['vCardName'];
					$mainarr[$i]['iCvvNo']= $getCardInfo[$i]['iCvvNo'];
					$mainarr[$i]['iCardTypeId']= $getCardInfo[$i]['iCardTypeId'];
					
					$base_path = $this->data['base_path'];
					$file_path =$base_path.'uploads/credit_card/'.$getCardInfo[$i]['vCardImage'];
					if (file_exists($file_path)) {
						$mainarr[$i]['image_url'] = $base_upload.'credit_card/'.$getCardInfo[$i]['vCardImage'];
					}else {
						$mainarr[$i]['image_url'] = 'No_image_available.jpg';   
					}
				}
				
				$Data['data'] = $mainarr;
				$Data['msg'] = 'Success';
			}else{
				$Data['msg'] = 'Error';
			}
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function AddOutOfStationAvailability(){
		if($this->input->post('iDriverId') && $this->input->post('vFromCity') && $this->input->post('vToCity') && $this->input->post('dFromDate') && $this->input->post('dToDate') && $this->input->post('iAdditionalDiscount')){
			$status = $this->webservices_model->check_driver_exists($this->input->post('iDriverId'));
			if($status=='exist'){
				// check max 10
				$allActive = $this->webservices_model->getActiveOutOfStationByDriver($this->input->post('iDriverId'));
				if (count($allActive) < 10) {
					// check Date
					$strFr = strtotime($this->input->post('dFromDate'));
					$strTo = strtotime($this->input->post('dToDate'));
					$now = strtotime(date('Y-m-d H:i:s'));
					$dFromDate = date('Y-m-d H:i:s',$strFr);
					$dToDate = date('Y-m-d H:i:s',$strTo);
					if ($strTo > $strFr && $strTo > $now && $strFr > $now) {
						$resFr = $this->webservices_model->checkDateforOutstation($this->input->post('iDriverId'),'dFromDate',$dFromDate,$dToDate);
						$resTo = $this->webservices_model->checkDateforOutstation($this->input->post('iDriverId'),'dToDate',$dFromDate,$dToDate);
						
						if (count($resFr)==0 && count($resTo)==0) {
							$OutofStationAvailability['iDriverId'] = $this->input->post('iDriverId');
							$OutofStationAvailability['vFromAddress'] = $this->input->post('vFromCity');
							$OutofStationAvailability['vToAddress'] = $this->input->post('vToCity');
							$OutofStationAvailability['dFromDate'] = $dFromDate;
							$OutofStationAvailability['dToDate'] = $dToDate;
							$OutofStationAvailability['vFromCity'] = $this->GetCityFromAddressOrLatLong($this->input->post('vFromCity'),'address');
							$OutofStationAvailability['vToCity'] = $this->GetCityFromAddressOrLatLong($this->input->post('vToCity'),'address');
							$OutofStationAvailability['iAdditionalDiscount'] = $this->input->post('iAdditionalDiscount');
							
							$iOutOfStationId = $this->webservices_model->add_outstation_availability($OutofStationAvailability);
							
							if ($iOutOfStationId>0) {
								$OutofStation_info = $this->webservices_model->getOutOfStation($iOutOfStationId);
								
								$OutofStationarr = array();
								$OutofStationarr['iOutOfStationId'] = $OutofStation_info['iOutOfStationId'];
								$OutofStationarr['iDriverId'] = $OutofStation_info['iDriverId'];
								$OutofStationarr['vFromCity'] = $OutofStation_info['vFromAddress'];
								$OutofStationarr['vToCity'] = $OutofStation_info['vToAddress'];
								$OutofStationarr['dFromDate'] = date('jS F Y g:i A',strtotime($OutofStation_info['dFromDate']));
								$OutofStationarr['dToDate'] =date('jS F Y g:i A',strtotime($OutofStation_info['dToDate']));
								$OutofStationarr['iAdditionalDiscount'] = $OutofStation_info['iAdditionalDiscount'];
								$Data['data'] = $OutofStationarr;
								$Data['msg'] = "Success";
							}else{
								$Data['msg'] = 'Not Added';
							}
						} else {
							$Data['msg'] = 'Outstation Availability is Already Exist Between Selected Dates';
						}
					} else {
						$Data['msg'] = 'Failure';
					}
				} else {
					$Data['msg'] = 'Maximum Limit is 10 for Outstation Availability';
				}
				
			}else{
				$Data['msg'] = 'Driver not available';
			}
		}else{
			$Data['msg'] = 'Error';
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function GetOutOfStationAvailability(){
		if($_REQUEST['source_address'] && $_REQUEST['destination_address']){
			$source_address = $_REQUEST['source_address'];
			$destination_address = $_REQUEST['destination_address'];
			$source_city = $this->GetCityFromAddressOrLatLong($source_address,'address');
			$source_city_status = $this->webservices_model->check_city_exist($source_city);
			if($source_city_status=='exist'){
				$destination_city = $this->GetCityFromAddressOrLatLong($destination_address,'address');
				$all_out_stations = $this->webservices_model->getAllOutOfStation(date('Y-m-d H:i:s'),$source_city,$destination_city);
				if (count($all_out_stations)>0) {
					$resultArr = array();
					foreach ($all_out_stations as $oskey => $osvalue) {
						$resultArr[$oskey]['iOutOfStationId'] = $osvalue['iOutOfStationId'];
						$resultArr[$oskey]['iDriverId'] = $osvalue['iDriverId'];
						$resultArr[$oskey]['vFirstName'] = $osvalue['vFirstName'];
						$resultArr[$oskey]['vLastName'] = $osvalue['vLastName'];
						$resultArr[$oskey]['iMobileNo'] = $osvalue['iMobileNo1'];
						$resultArr[$oskey]['vCompany'] = $osvalue['vCompany'];
						$resultArr[$oskey]['vModelName'] = $osvalue['vModelName'];
						$resultArr[$oskey]['vToAddress'] = $osvalue['vToAddress'];
						$resultArr[$oskey]['vToCity'] = $osvalue['vToAddress'];
						$resultArr[$oskey]['dToDate'] = $osvalue['dToDate'];
						$resultArr[$oskey]['iAdditionalDiscount'] = $osvalue['iAdditionalDiscount'];
					}
					$data['data'] = $resultArr;
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "No Record Found";
				}
			}else {
				$data['msg'] = "Service Not Available In Your Pick UP Address City";
			}
		}else{
			$data['msg'] = 'Error';
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function StatusOfOutOfStation(){
		if($this->input->post('iDriverId') && $this->input->post('type')){
			
			$type = $this->input->post('type');
			if($type =='Active'){
				$OutofStation_info = $this->webservices_model->getOutOfStationByType($this->input->post('iDriverId'),$type); 
				if ($OutofStation_info) {
				   $Data['data'] = $OutofStation_info;
					$Data['msg'] = "Success";
				}else{
					$Data['msg'] = 'Failure';
				}     
			}elseif($type =='Expired'){
				$OutofStation_info = $this->webservices_model->getOutOfStationByType($this->input->post('iDriverId'),$type); 
				if ($OutofStation_info) {
				   $Data['data'] = $OutofStation_info;
					$Data['msg'] = "Success";
				}else{
					$Data['msg'] = 'Failure';
				}  
			}else{
					$Data['msg'] = 'error';
				}     
		}else{
			 $Data['msg'] = 'Error';
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function GetCountryList(){
		$countryList = $this->webservices_model->get_all_country();
		if (count($countryList)>0) {
			$Data['data'] = $countryList;
			$Data['msg'] = "Success";
		} else {
			$Data['msg'] = "No Record Found";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CheckTripLocalOutstation(){
		if($_REQUEST['source_address'] && $_REQUEST['destination_address']){
			$source_address_city = $this->GetCityFromAddressOrLatLong($_REQUEST['source_address'],'address');
			$source_city_status = $this->webservices_model->check_city_exist($source_address_city);
			if ($source_city_status=='exist') {
				$sourceCityDetail = $this->webservices_model->getCityId($source_address_city);
				$finishlatlong = $this->GetLatLongFromAddress($_REQUEST['destination_address']);
				$distance = $this->GetMileKMFromLatLong($sourceCityDetail['tCityLatLong'],$finishlatlong);
				if ($sourceCityDetail['eRadiusUnit']=='Miles') {
					$data['msg'] = ($distance['miles']<=$sourceCityDetail['fRadius']) ? "Local" : "OutStation" ;
				} else {
					$data['msg'] = ($distance['kms']<=$sourceCityDetail['fRadius']) ? "Local" : "OutStation" ;
				}
			} else {
				$data['msg'] = "Service Not Available In Your Pick UP Address City";
			}
		}else{
			$data['msg'] = 'Error';
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function MakeExpireOutStationAvailability(){
		if($_REQUEST['iDriverId'] && $_REQUEST['iOutOfStationId']){
			$status = $this->webservices_model->check_out_station_by_driver($_REQUEST['iDriverId'],$_REQUEST['iOutOfStationId']);
			if ($status==1) {
				$res = $this->webservices_model->expire_out_station_by_driver($_REQUEST['iDriverId'],$_REQUEST['iOutOfStationId']);
				$data['msg'] = 'Success';
			} else {
				$data['msg'] = 'No Record Found';
			}
			
		}else{
			$data['msg'] = 'Error';
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function CancelTrip(){
		if($_REQUEST['iTripId']){
			$iTripId = $_REQUEST['iTripId'];
			$checktripExist = $this->webservices_model->check_Exist('iTripId',$iTripId,'trip_detail');
			if($checktripExist > 0) {
				$trip_details = $this->webservices_model->get_only_one_trip_details($iTripId);
				$mycurrency = $this->webservices_model->get_city_currency($trip_details['iCityId']);
				$tripCityDetail = $this->webservices_model->cityDetailByTripID($iTripId);

				/*$datetime2 = new DateTime($trip_details['dBookingDate']);
				$datetime1 = new DateTime("now", new DateTimeZone($tripCityDetail['vTimeZone']));
				$diff = $datetime1->diff($datetime2);*/
				//-------------------------
				$bookTime = new DateTime($trip_details['dBookingDate']);
				$driverTime = new DateTime('now', new DateTimeZone($tripCityDetail['vTimeZone']));
				$acceptTime = $driverTime->format('Y-m-d H:i:s');
				$compare = new DateTime($acceptTime);
				$diff = $compare->diff($bookTime);
				//-------------------------
				/*$this->printthis("Diff");
				$this->printthisexit($diff->i);*/
				if ($diff->i < 5 ) {
					$cancellation_amt = 0.00;
				} else {
					$cancellation_amt = $this->webservices_model->get_cancellation_charge_by_city($trip_details['iCityId']);
					if ($trip_details['ePaymentType']=='Cash') {
						// echo "Send email\n";
						$transactionarr['iCardTypeId'] = "";
						$transactionarr['eType'] = 'Cash';
						$transactionarr['vPaypalTransactionId'] = "";
					} else {
						// echo "Cut Charge from card : ".$cancellation_amt."\n";
						$riderCreditCardDetails = $this->webservices_model->get_client_credit_card_information($trip_details['iClientId']);
						$transactionarr['iCardTypeId'] = $riderCreditCardDetails['iCardTypeId'];
						$transactionarr['eType'] = 'Credit';
						$striperesarr = $this->stripepayment($riderCreditCardDetails['iTransactionId'],$trip_details['iClientId'],$cancellation_amt,$mycurrency['vCurrencyCode']);
						$transactionarr['vPaypalTransactionId'] = $striperesarr['message'];
						if($striperesarr['error_status']=='yes'){
							$stripepaymentmessage = $striperesarr['message'];
							$dataarr['data'] = $stripepaymentmessage;
							$dataarr['msg'] = 'Failure';
						}
					}
				}

				$device_details = $this->webservices_model->get_rider_device_details_for_cancel($iTripId);
				if($device_details){
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = "Trip has been cancelled successfully!";
					$pushNotificationData['vDeviceid'] = $device_details['device_id'];
					$pushNotificationData['eUserType'] = "Rider";
					$datapush = $this->pushNotification($pushNotificationData);
				}
				$totaldelrows = $this->webservices_model->cancel_trip_details($iTripId,$cancellation_amt,$riderCreditCardDetails['iTransactionId']);
				$trip_driver_details = $this->webservices_model->get_trip_driver_details_for_cancel($iTripId);

				if(count($trip_driver_details)>0){
					foreach ($trip_driver_details as $key => $value) {
						$driver_device_details = $this->webservices_model->get_driver_device_details($value['iDriverId']);
						$customer_full_name = $trip_details['ClientFirstName'].' '.$trip_details['ClientLastName'];
						$pushNotificationData['action'] = 'sendNotification';
						$pushNotificationData['msg'] = $customer_full_name." has cancelled the trip";
						//$pushNotificationData['msg'] = $customer_full_name." has cancelled the Trip From: ".$trip_details['vPickupLocation'].' To: '.$trip_details['vDestinationLocation'].'@'.$iTripId;
						$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
						$pushNotificationData['eUserType'] = "Driver";
						$datapush = $this->pushNotification($pushNotificationData);
						if($value['eDriverAssign']=='Yes'){
							$transactionarr['iDriverId'] = $value['iDriverId'];    
						}
						else {
							$transactionarr['iDriverId'] = "";    
							$totaldriverrows = $this->webservices_model->delete_trip_drivers($iTripId);
						}
					}
				}
				else {
					$transactionarr['iDriverId'] = "";    
				}
				
				// code for transaction
				
				$transactionarr['fAmount'] = number_format($cancellation_amt,2);
				$transactionarr['fCompanyAmount'] = number_format($cancellation_amt,2);
				$transactionarr['fDriverAmount'] = '0.00';
				
				
				$transactionarr['dDate'] = date('Y-m-d H:i:s');
				$transactionarr['ePayStatus'] = 'Not Paid';
				
				$iTransactionId = $this->webservices_model->save_transaction_cancel_trip($transactionarr);
				$totalrows = $this->webservices_model->update_trip_transaction($iTransactionId,$iTripId);

				$my_base_url = $this->data['base_url'];

				// end of code for transaction
				$cancellation_amt = $this->data['CURRENCY'].$cancellation_amt;
				$client_full_name = $trip_details['ClientFirstName'].' '.$trip_details['ClientLastName'];
				$bodyArr = array("#NAME#","#CANCELLATION_CHARGES#","#SOURCE#","#DESTINATION#","#IMAGE_URL#");
				$postArr = array($client_full_name,$cancellation_amt,$trip_details['vPickupLocation'],$trip_details['vDestinationLocation'],$my_base_url);
				$sendClient=$this->Send("CUSTOMER_CANCELATTION_INVOICE","Client",$trip_details['ClientEmail'],$bodyArr,$postArr);


				// check if trip partner exist or not
				$all_partners = $this->webservices_model->get_all_trip_partners_info($trip_details['iClientId'],$iTripId);
				foreach ($all_partners as $key => $value) {
					$client_full_name = $value['vFirstName'].' '.$value['vLastName'];
					$bodyArr = array("#NAME#","#CANCELLATION_CHARGES#","#SOURCE#","#DESTINATION#","#IMAGE_URL#");
					$postArr = array($client_full_name,$cancellation_amt,$trip_details['vPickupLocation'],$trip_details['vDestinationLocation'],$my_base_url);
					$sendClient=$this->Send("CUSTOMER_CANCELATTION_INVOICE","Client",$value['vEmail'],$bodyArr,$postArr);   
				}
				// end of code for trip partner exist or not 
				$dataarr['data'] = '';
				$dataarr['msg'] = "Success";
			}
			else{
				$dataarr['data'] = '';
				$dataarr['msg'] = "Trip Not Exist";
			}
		}
		else {
			$dataarr['data'] = '';
			$dataarr['msg']="Failure"; 
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($dataarr);
		echo $callback . ''.$main.'';
		// return $callback . ''.$main.'';
		exit;
	}

	// Helper function to check given time is between time interval
	function timeisBetween($from, $till, $input) {
		$f = DateTime::createFromFormat('!H:i', $from);
		$t = DateTime::createFromFormat('!H:i', $till);
		$i = DateTime::createFromFormat('!H:i', $input);
		if ($f > $t) $t->modify('+1 day');
		return ($f <= $i && $i <= $t) || ($f <= $i->modify('+1 day') && $i <= $t);
	}

	function AutoCancelationTrip_BK(){
		// Cron for Auto Cancelation Trip : call after 5 mins
		// Test cron
		// $current_date_time = strtotime(date('Y-m-d H:i:s'));
		// $cronRes = $this->webservices_model->save_data(array('dExecuteTime'=> date('Y-m-d H:i:s'),'vNote'=>'AutoCancelationTrip'), 'cron_test');
		/*$all_trips = $this->webservices_model->get_all_pending_trips();

		foreach ($all_trips as $key_all => $value_all) {
			$source_city = $this->webservices_model->cityDetailByTripID($value_all['iTripId']);
			$tmpDate = new DateTime("now", new DateTimeZone($source_city['vTimeZone']));
			$current_date_time = strtotime($tmpDate->format('Y-m-d H:i:s'));
			$bookingdatetime = strtotime($value_all['dTripDate']);
			
			if ($value_all['eTripLocation']=='Local' || $value_all['eType']=='Fixride'  || ($value_all['eTripLocation']=='Outstation' && $value_all['eTripType']=='Round')){
				if($value_all['eDriverAssign']=='No'){
					$finalmins = round(abs($current_date_time - $bookingdatetime) / 60,2);
					if(($value_all['eBookType']=='Now' && $finalmins>2) || ($value_all['eBookType']=='Later' && $value_all['eBookLaterNotification']=='Yes' && ($finalmins<118 && $finalmins>115))){
						// cancel and send notification to customer
						$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
						$cancelRes = $this->webservices_model->cancel_trip_by_no_service_available($value_all['iTripId']);
						$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
						$pushNotificationData['action'] = 'sendNotification';
						$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
						$pushNotificationData['vDeviceid'] = $client_device;
						$pushNotificationData['eUserType'] = "Rider";
						$datapush = $this->pushNotification($pushNotificationData);
					}
				}
			}

			if ($value_all['eTripLocation']=='Outstation' && $value_all['eTripType']=='One Way'){
				// outstation and one way
				if($value_all['eDriverAssign']=='No'){
					$finalmins = round(abs($current_date_time - $bookingdatetime) / 60,2);
					if($value_all['eOutstationNotification']=='Yes'){
						if(($value_all['eBookType']=='Now' && ($finalmins < 4 && $finalmins>2)) || ($value_all['eBookType']=='Later' && $value_all['eBookLaterNotification']=='Yes' && ($finalmins<118 && $finalmins>115))){
							// first remove other drivers from trip_drivers
							$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
							// check for source city if out-station / both drivers are available
							$cityDetail = $this->webservices_model->get_one_city_details_byid($value_all['iCityId']);
							$qurVar = ($cityDetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
							$source_city_lat_long = explode('|', $cityDetail['tCityLatLong']);
							// check drivers available in source city
							$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$cityDetail['fRadius'],$qurVar,array('Outstation','Both'),$value_all['iVehicleCompanyId']);
							
							if ($drivers) {
								$driverCtr=0;
								foreach ($drivers as $dkey => $dvalue) {
									$countRunningTrip = $this->webservices_model->checkRunningTripByDriver($dvalue['iDriverId']);
									if($countRunningTrip==0){
										// get device of driver
										$driver_device_details = $this->webservices_ridein_model->get_driver_device_details($nearest_driver_id);
										if($driver_device_details){
											$pushNotificationData['action'] = 'sendNotification';
											$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$value_all['iTripId'];
											$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
											$pushNotificationData['eUserType'] = "Driver";
											$datapush = $this->pushNotification($pushNotificationData);
											$driverCtr++;
										}
									}
								}
								if($driverCtr==0){
									// cancel the trip and send notification to customer
									$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
									$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
									$pushNotificationData['action'] = 'sendNotification';
									$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
									$pushNotificationData['vDeviceid'] = $client_device;
									$pushNotificationData['eUserType'] = "Rider";
									$datapush = $this->pushNotification($pushNotificationData);	
								}
							} else {
								// cancel the trip and send notification to customer
								$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
								$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
								$pushNotificationData['action'] = 'sendNotification';
								$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
								$pushNotificationData['vDeviceid'] = $client_device;
								$pushNotificationData['eUserType'] = "Rider";
								$datapush = $this->pushNotification($pushNotificationData);
							}
						}
						if(($value_all['eBookType']=='Now' && $finalmins >= 4) || ($value_all['eBookType']=='Later' && $value_all['eBookLaterNotification']=='Yes' && ($finalmins<115 && $finalmins>112))){
							// cancel and send notification to customer
							$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
							$cancelRes = $this->webservices_model->cancel_trip_by_no_service_available($value_all['iTripId']);
							$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
							$pushNotificationData['vDeviceid'] = $client_device;
							$pushNotificationData['eUserType'] = "Rider";
							$datapush = $this->pushNotification($pushNotificationData);
						}
					}
					if($value_all['eOutstationNotification']=='No'){
						if(($value_all['eBookType']=='Now' && $finalmins>2) || ($value_all['eBookType']=='Later' && $value_all['eBookLaterNotification']=='Yes' && ($finalmins<118 && $finalmins>115))){
							// cancel and send notification to customer
							$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
							$cancelRes = $this->webservices_model->cancel_trip_by_no_service_available($value_all['iTripId']);
							$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
							$pushNotificationData['vDeviceid'] = $client_device;
							$pushNotificationData['eUserType'] = "Rider";
							$datapush = $this->pushNotification($pushNotificationData);
						}
					}
				}
			}
		}*/
		echo 'Finish';exit;
	}

	function AutoCancelationTrip(){
		// Cron for Auto Cancelation Trip : call after 5 mins
		// Test cron
		// $current_date_time = strtotime(date('Y-m-d H:i:s'));
		// $cronRes = $this->webservices_model->save_data(array('dExecuteTime'=> date('Y-m-d H:i:s'),'vNote'=>'AutoCancelationTrip'), 'cron_test');

		// '','Outstation','Local','CarPoolLocal','CarPoolOutstation','Shuttle'

		$all_trips = $this->webservices_model->get_all_pending_trips();
		foreach ($all_trips as $key_all => $value_all) {
			$source_city = $this->webservices_model->cityDetailByTripID($value_all['iTripId']);
			$tmpDate = new DateTime("now", new DateTimeZone($source_city['vTimeZone']));
			$current_date_time = strtotime($tmpDate->format('Y-m-d H:i:s'));
			$bookingdatetime = strtotime($value_all['dTripDate']);

			$finalmins = round(abs($current_date_time - $bookingdatetime) / 60,2);
			if($value_all['eOutstationNotification']=='Yes'){
				if($finalmins >= 10){
					// delete trip-drivers
					$this->webservices_model->delete_trip_drivers($value_all['iTripId']);
					// cancel trip
					$this->webservices_model->cancel_trip_by_no_service_available($value_all['iTripId']);
					// get customer device to send notification
					$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
					$pushNotificationData['vDeviceid'] = $client_device;
					$pushNotificationData['eUserType'] = "Rider";
					$datapush = $this->pushNotification($pushNotificationData);
				}else if($finalmins < 10 && $finalmins>5){
					// first remove other drivers from trip_drivers
					$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
					// check for source city if out-station / both drivers are available
					$cityDetail = $this->webservices_model->get_one_city_details_byid($value_all['iCityId']);
					$qurVar = ($cityDetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
					$source_city_lat_long = explode('|', $cityDetail['tCityLatLong']);
					// check drivers available in source city
					$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$cityDetail['fRadius'],$qurVar,array('Outstation','Both'),$value_all['iVehicleCompanyId']);
					
					if ($drivers) {
						$driverCtr=0;
						foreach ($drivers as $dkey => $dvalue) {
							$countRunningTrip = $this->webservices_model->checkRunningTripByDriver($dvalue['iDriverId']);
							if($countRunningTrip==0){
								// get device of driver
								$driver_device_details = $this->webservices_ridein_model->get_driver_device_details($nearest_driver_id);
								if($driver_device_details){
									$pushNotificationData['action'] = 'sendNotification';
									$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$value_all['iTripId'];
									$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
									$pushNotificationData['eUserType'] = "Driver";
									$datapush = $this->pushNotification($pushNotificationData);
									$driverCtr++;
								}
							}
						}
						if($driverCtr==0){
							// cancel the trip and send notification to customer
							$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
							$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
							$pushNotificationData['vDeviceid'] = $client_device;
							$pushNotificationData['eUserType'] = "Rider";
							$datapush = $this->pushNotification($pushNotificationData);	
						}
					} else {
						// cancel the trip and send notification to customer
						$totdelrows = $this->webservices_model->delete_trip_drivers($value_all['iTripId']);
						$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
						$pushNotificationData['action'] = 'sendNotification';
						$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
						$pushNotificationData['vDeviceid'] = $client_device;
						$pushNotificationData['eUserType'] = "Rider";
						$datapush = $this->pushNotification($pushNotificationData);
					}
				}
			}else{
				if($finalmins >= 5){
					// delete trip-drivers
					$this->webservices_model->delete_trip_drivers($value_all['iTripId']);
					// cancel trip
					$this->webservices_model->cancel_trip_by_no_service_available($value_all['iTripId']);
					// get customer device to send notification
					$client_device = $this->webservices_model->get_client_device_details($value_all['iClientId']);
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = "No Service Available At This Time, Please Try Again Later";
					$pushNotificationData['vDeviceid'] = $client_device;
					$pushNotificationData['eUserType'] = "Rider";
					$datapush = $this->pushNotification($pushNotificationData);
				}
			}
		}
		echo 'Finish';exit;
	}

	function GetLocalRoundOptions(){
		if($this->input->post('source_address')){
			// Get city
			$startlatlong = $this->GetLatLongFromAddress($this->input->post('source_address'));
			$source_lat_long_arr = explode('|', $startlatlong);
			$dLatitude=$source_lat_long_arr[0];
			$dLongitude=$source_lat_long_arr[1];
			$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKMtest($dLatitude,$dLongitude);
			$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMiletest($dLatitude,$dLongitude);
			// check within radius
			$check_km=($source_city_status_km['distance'] <= $source_city_status_km['fRadius']) ? $source_city_status_km : 0;
			$check_mile = ($source_city_status_mile['distance'] <= $source_city_status_mile['fRadius']) ? $source_city_status_mile : 0;
			if ($check_km != 0 || $check_mile != 0) {
				if ($check_km != 0 && $check_mile == 0) {
					$source_citydetail = $check_km;
				}else if ($check_mile != 0 && $check_km == 0) {
					$source_citydetail = $check_mile;
				}else if ($check_mile != 0 && $check_km != 0) {
					$distanceFromKM = $check_km['distance'];
					$distanceFromMile=$check_mile['distance']*1.609344;
					$source_citydetail = ($distanceFromKM < $distanceFromMile) ? $check_km : $check_mile ;
				}else{
					$data['msg'] = "Service Not Available In Your Pick UP Address City";
					echo json_encode($data);exit;
				}
			} else {
				$data['msg'] = "Service Not Available In Your Pick UP Address City";
				echo json_encode($data);exit;
			}
			// Get fare details of city
			$fareOptions = $this->webservices_model->getFareOptions($source_citydetail['iCityId']);
			$option=explode(',',$fareOptions['lOptions']);
			foreach ($option as $fkey => $fare) {
				$str=explode('|',$fare);
				if($str[1]!=''){
					$resultArr[]=$str[0];
				}
			}
			if (count($resultArr) > 0 ) {
				$data['data'] = $resultArr;
				$data['msg'] = "Success";
			}else{
				$data['msg'] = "Service Not Available In Your Pick UP Address City";
			}
		}else {
			$data['msg'] = "Failure";
		}
		echo json_encode($data);exit;
	}

	// With Time Zone
	function GetFareDurationDistanceBySourceDestination(){
		if($this->input->post('latitude') && $this->input->post('longitude') && $this->input->post('source_address') && $this->input->post('car_id') && $this->input->post('booktype')  ){
			$source_address = mysql_real_escape_string($this->input->post('source_address'));
			$source_address_city = $this->GetCityFromAddressOrLatLong($source_address,'address');
			$source_address_city_status = $this->webservices_model->check_city_exist($source_address_city);
			// check source city is available for service
			if($source_address_city_status=='exist'){
				// get source city detail
				$source_citydetail = $this->webservices_model->get_one_city_details($source_address_city);
				$currencySymbol = $source_citydetail['vCurrencySymbol'];
				
				$distanceUnit = $source_citydetail['eRadiusUnit'];
				$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
				$source_city_lat_long = explode('|', $source_citydetail['tCityLatLong']);
				$source_city_radius =$source_citydetail['fRadius'];
				$ridelocation = $this->input->post('ridelocation'); // local / outstation
				if ($ridelocation=='local') {
					$ridelocationArr[] = 'Local';
					$ridelocationArr[] = 'Both';
				} else {
					$ridelocationArr[] = 'Outstation';
					$ridelocationArr[] = 'Both';
				}
				
				$ride_id = $this->input->post('ride_id');
				$car_id = $this->input->post('car_id');

				$booktype = $this->input->post('booktype'); // now / later
				$ridetype = $this->input->post('ridetype'); // oneway / round

				// check drivers available in source city
				$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$source_city_radius,$qurVar,$ridelocationArr,$car_id);
				// $this->printthisexit($drivers);
				$availableDriverCount = 0;
				foreach ($drivers as $drkey => $driver) {
					$all_trips_by_driver=$this->webservices_model->get_all_trip_details_by_driver_id_book_ride($driver['iDriverId']);
					if(count($all_trips_by_driver)==0){
						$availableDriverCount++;
					}
				}

				if($ride_id!=0){
					// This is a fix ride
					$fixridedetail = $this->webservices_model->get_fix_ride_details($ride_id);
					if (!empty($fixridedetail)) {
						$fixridefaredetail = $this->webservices_model->get_fix_ride_fare_details($ride_id,$car_id);
						if($fixridefaredetail>0){
							$carDetail = $this->webservices_model->get_one_vehicle_detail_byId($car_id);
							$mainarr['taxi_name'] = $carDetail['vCompany'];
							$mainarr['total_fare'] = $currencySymbol.$fixridefaredetail;
							$mainarr['seating_capacity'] = $carDetail['vSeatingCapacity'];
							$mainarr['source_address'] = $fixridedetail['vPickUpLocation'];
							$mainarr['destination_address'] = $fixridedetail['vDropLocation'];
							$mainarr['duration'] = $this->ConvertHrToMin($fixridedetail['fDuration']);
							$mainarr['distance'] = $fixridedetail['fDistance']." ".strtoupper($distanceUnit);
							$data['data'] = $mainarr;
							if ($booktype=='now' && $availableDriverCount == 0) {
								$data['msg'] = 'No Car Found in Pick UP Location';
							}else{
								$data['msg'] = 'Success';
							}
						}else {
							$data['msg'] = "No Fare Available";
						}
					}else{
						$data['msg'] = "Failure";
					}
				}else if($ride_id==0){
					// This is not a fix ride
					// 1. local and oneway : Done
					if($ridelocation=='local'&& $ridetype=='oneway') {
						// Consider : BaseFare, MinimumFare, MinimumMiles, perMileFare, PerMinuteFare
						$fare_quote = $this->webservices_model->get_fare_quote_details_new($source_citydetail['iCityId'],$car_id,$ridelocation,'One Way');
						if(!empty($fare_quote)){
							if ($this->input->post('destination_address')) {
								$destination_address = mysql_real_escape_string($this->input->post('destination_address'));
								if ($booktype=='now') {
									$tmpDate = new DateTime("now", new DateTimeZone($source_citydetail['vTimeZone']));
									$book_time = $tmpDate->format('Y-m-d H:i:s');
								} else {
									$book_time = $this->input->post('book_time');
								}
								// lat longs
								$startlatlong = $this->GetLatLongFromAddress($source_address);
								$finishlatlong = $this->GetLatLongFromAddress($destination_address);

								// get distance
								$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);

								if($finalkmminarr!='none'){
									$mainarr['taxi_name'] = $fare_quote['vCompany'];
									if ($distanceUnit=='KMs') {
										$distance = $finalkmminarr['distance_in_km'];
										$mainarr['distance'] = $distance.' KMS'; 
									} else {
										$distance = $finalkmminarr['distance_in_mile'];
										$mainarr['distance'] = $distance.' MILES'; 
									}
									$mainarr['duration'] = strtoupper($finalkmminarr['original_duration']);
									if($distance < $fare_quote['fMinimumKm']){
										$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
									}else {
										
										$perMileFare = 0;
										$finalfare = $fare_quote['fBaseFare'];
										$current_hr_min=date('H:i', strtotime($book_time));
										if ($source_citydetail['ePicksurchargestatus']=='Yes') {
											$vPicktimefrom = date('H:i', strtotime($source_citydetail['vPicktimefrom']));
											$vPicktimeto = date('H:i', strtotime($source_citydetail['vPicktimeto']));
											if ($this->timeisBetween($vPicktimefrom,$vPicktimeto,$current_hr_min))
											{
												$perMileFare = $fare_quote['fPerMileFare']+(($fare_quote['fPerMileFare']*$fare_quote['fPeaktimesurcharge'])/100);
											}
										}
										if ($source_citydetail['eNightsurchargestatus']=='Yes') {
											$vNighttimefrom = date('H:i', strtotime($source_citydetail['vNighttimefrom']));
											$vNighttimeto = date('H:i', strtotime($source_citydetail['vNighttimeto']));
											if ($this->timeisBetween($vNighttimefrom,$vNighttimeto,$current_hr_min))
											{
												$perMileFare = $fare_quote['fPerMileFare'] + ($fare_quote['fPerMileFare'] * ($fare_quote['fNighttimesurcharge']) / 100);
											}
										}
										$perMileFare = ($perMileFare==0) ? $fare_quote['fPerMileFare'] : $perMileFare ;
										
										$totalfareinmiles = ($perMileFare * $distance);
										$totalfareinmins = ($fare_quote['fPerMinFare'] * $finalkmminarr['duration_in_mins']);
										$finalfare = $finalfare+($totalfareinmiles+$totalfareinmins);
										$finalfare = $finalfare + (($finalfare * $source_citydetail['fServicetax'])/100);
										if (number_format($fare_quote['fThreeKmFare'], 2) > $finalfare) {
											$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
										} else {
											$mainarr['total_fare'] = $currencySymbol.(number_format($finalfare,2));
										}
									}
									$mainarr['seating_capacity'] = $fare_quote['vSeatingCapacity'];
									$mainarr['source_address'] = $source_address;
									$mainarr['destination_address'] = $destination_address;
									$data['data'] = $mainarr;
									if ($booktype=='now' && $availableDriverCount == 0) {
										$data['msg'] = 'No Car Found in Pick UP Location';
									} else {
										$data['msg'] = 'Success';
									}
								}else{
									$data['msg'] = "Failure";
								}
							}
						}else{
							$data['msg'] = "No Fare Available";
						}
					}
					// 2. local and round : Done
					if($ridelocation=='local'&& $ridetype=='round') {
						// Consider : fOption1, fOption2, fOption3, fOption4
						$roundOption = $this->input->post('vRoundOption');
						$fare_quote = $this->webservices_model->get_fare_quote_details_new($source_citydetail['iCityId'],$car_id,$ridelocation,'Round');

						if(!empty($fare_quote)){
							if ($this->input->post('destination_address') && $roundOption !="") {
								$additionalFare=0;
								$totalfareinmins = 0;
								$destination_address = mysql_real_escape_string($this->input->post('destination_address'));
								// lat longs
								$startlatlong = $this->GetLatLongFromAddress($source_address);
								$finishlatlong = $this->GetLatLongFromAddress($destination_address);

								// get distance
								$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);

								if($finalkmminarr!='none'){
									$mainarr['taxi_name'] = $fare_quote['vCompany'];
									$optionArr = explode('/', $roundOption);
									$hourPart = trim($optionArr[0]);
									$distancePart = trim($optionArr[1]);
									switch ($hourPart) {
										case '8 Hrs':
											$part = strpos($distancePart, '80 '.$distanceUnit);
											if ($part===false) {
												$selectedOption = 'fOption2';
												$minFare = $fare_quote['fOption2'];
												$maxMins = 480;
											}else{
												$selectedOption = 'fOption1';
												$minFare = $fare_quote['fOption1'];
												$maxMins = 480;
											}
											break;
										case '10 Hrs':
											$selectedOption = 'fOption3';
											$minFare = $fare_quote['fOption3'];
											$maxMins = 600;
											break;
										case '12 Hrs':
											$selectedOption = 'fOption4';
											$minFare = $fare_quote['fOption4'];
											$maxMins = 720;
											break;
									}
									
									if ($distanceUnit=='KMs') {
										$distance = $finalkmminarr['distance_in_km']*2;
										$mainarr['distance'] = $distance.' KMS'; 
									} else {
										$distance = $finalkmminarr['distance_in_mile']*2;
										$mainarr['distance'] = $distance.' MILES'; 
									}
									if ($selectedOption == 'fOption1' && $distance > 80) {
										$additionalFare = ($fare_quote['fPerMileFare'] * ($distance-80));
									}
									$totalMinutes = $finalkmminarr['duration_in_mins']*2;
									
									if ($totalMinutes > $maxMins) {
										$totalfareinmins = ($fare_quote['fPerMinFare'] * ($totalMinutes - $maxMins));
									}
									$mainarr['duration'] = $this->ConvertHrToMin($totalMinutes);
									$finalfare = $totalfareinmins+$additionalFare;
									$finalfare = $finalfare + $minFare;
									$finalfare = $finalfare + (($finalfare * $source_citydetail['fServicetax'])/100);
									$mainarr['total_fare'] = $currencySymbol.(number_format($finalfare,2));
									$mainarr['seating_capacity'] = $fare_quote['vSeatingCapacity'];
									$mainarr['source_address'] = $source_address;
									$mainarr['destination_address'] = $destination_address;
									$data['data'] = $mainarr;
									if ($booktype=='now' && $availableDriverCount == 0) {
										$data['msg'] = 'No Car Found in Pick UP Location';
									} else {
										$data['msg'] = 'Success';
									}
								}else{
									$data['msg'] = "Failure";
								}
							}else{
								$data['msg'] = "Failure";
							}
						}else{
							$data['msg'] = "No Fare Available";
						}
					}
					// 3. outstation and oneway
					if($ridelocation=='outstation'&& $ridetype=='oneway') {
						// Consider : MinimumFare, MinimumMiles, perMileFare
						$fare_quote = $this->webservices_model->get_fare_quote_details_new($source_citydetail['iCityId'],$car_id,$ridelocation,'One Way');
						if(!empty($fare_quote)){
							if ($this->input->post('destination_address')) {
								$destination_address = mysql_real_escape_string($this->input->post('destination_address'));
								// lat longs
								$startlatlong = $this->GetLatLongFromAddress($source_address);
								$finishlatlong = $this->GetLatLongFromAddress($destination_address);

								// get distance
								$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);

								if($finalkmminarr!='none'){
									$mainarr['taxi_name'] = $fare_quote['vCompany'];
									if ($distanceUnit=='KMs') {
										$distance = $finalkmminarr['distance_in_km'];
										$mainarr['distance'] = $distance.' KMS'; 
									} else {
										$distance = $finalkmminarr['distance_in_mile'];
										$mainarr['distance'] = $distance.' MILES'; 
									}
									$mainarr['duration'] = strtoupper($finalkmminarr['original_duration']);
									if($distance < $fare_quote['fMinimumKm']){
										$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
									}else {
										$finalfare = ($fare_quote['fPerMileFare'] * $distance);
										$finalfare = $finalfare + (($finalfare * $source_citydetail['fServicetax'])/100);
										if (number_format($fare_quote['fThreeKmFare'], 2) > $finalfare) {
											$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
										} else {
											$mainarr['total_fare'] = $currencySymbol.(number_format($finalfare,2));
										}
									}
									$mainarr['seating_capacity'] = $fare_quote['vSeatingCapacity'];
									$mainarr['source_address'] = $source_address;
									$mainarr['destination_address'] = $destination_address;
									$data['data'] = $mainarr;
									if ($booktype=='now' && $availableDriverCount == 0) {
										$data['msg'] = 'No Car Found in Pick UP Location';
									} else {
										$data['msg'] = 'Success';
									}
								}else{
									$data['msg'] = "Failure";
								}
							}
						}else{
							$data['msg'] = "No Fare Available";
						}
					}
					// 4. outstation and round
					if($ridelocation=='outstation'&& $ridetype=='round') {
						// Consider : MinimumMiles, perMileFare, selected day
						$roundOption = $this->input->post('vRoundOption');
						$fare_quote = $this->webservices_model->get_fare_quote_details_new($source_citydetail['iCityId'],$car_id,$ridelocation,'Round');
						if(!empty($fare_quote)){
							if ($this->input->post('destination_address') && $roundOption !="") {
								$additionalFare=0;
								
								$destination_address = mysql_real_escape_string($this->input->post('destination_address'));
								// lat longs
								$startlatlong = $this->GetLatLongFromAddress($source_address);
								$finishlatlong = $this->GetLatLongFromAddress($destination_address);

								// get distance
								$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);

								if($finalkmminarr!='none'){
									$mainarr['taxi_name'] = $fare_quote['vCompany'];
									if ($distanceUnit=='KMs') {
										$distance = $finalkmminarr['distance_in_km']*2;
										$mainarr['distance'] = $distance.' KMS'; 
									} else {
										$distance = $finalkmminarr['distance_in_mile']*2;
										$mainarr['distance'] = $distance.' MILES'; 
									}
									$totalTime = $finalkmminarr['duration_in_mins'] * 2;
									
									$mainarr['duration'] = $this->ConvertHrToMin($totalTime);

									if($roundOption=='Full Day'){
										if($distance < $fare_quote['fMinimumKm']){
											$finalfare = ($fare_quote['fPerMileFare'] * $fare_quote['fMinimumKm']);
										}else {
											$finalfare = ($fare_quote['fPerMileFare'] * $distance);
										}
									}else{
										$optionArr = explode(' ', $roundOption);
										$dayPart = number_format(trim($optionArr[0]));
										if($distance < ($fare_quote['fMinimumKm'] * $dayPart)){
											$finalfare = ($fare_quote['fPerMileFare'] * $fare_quote['fMinimumKm'] * $dayPart);
										}else {
											$finalfare = ($fare_quote['fPerMileFare'] * $distance);
										}
										
										$finalfare = $finalfare + ($dayPart-1)*$fare_quote['fOvernightallowence'];
									}
									$finalfare = $finalfare + (($finalfare * $source_citydetail['fServicetax'])/100);
									$mainarr['total_fare'] = $currencySymbol.(number_format($finalfare, 2));
									$mainarr['seating_capacity'] = $fare_quote['vSeatingCapacity'];
									$mainarr['source_address'] = $source_address;
									$mainarr['destination_address'] = $destination_address;
									$data['data'] = $mainarr;
									if ($booktype=='now' && $availableDriverCount == 0) {
										$data['msg'] = 'No Car Found in Pick UP Location';
									} else {
										$data['msg'] = 'Success';
									}
								}else{
									$data['msg'] = "Failure";
								}
							}else{
								$data['msg'] = "Failure";
							}
						}else{
							$data['msg'] = "No Fare Available";
						}
					}
				}else{
					$data['msg'] = "Failure";
				}
			}else {
				$data['msg'] = "Service Not Available In Your Pick UP Address City";
			}
		} // end of main if
		else {
			$data['msg'] = "Failure";
		} // end of main else
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;   
	}

	function ConfirmTripByDriver(){
		if($this->input->post('iTripId') && $this->input->post('iDriverId')){
			$trip_id = $this->input->post('iTripId');
			$driver_id = $this->input->post('iDriverId');
			$trip_exist = $this->webservices_model->check_trip_exists($trip_id);
			$driver_status = $this->webservices_model->check_driver_exists($driver_id);
			if($trip_exist=='exist'){
				if($driver_status=='exist'){
					// check trip canceled
					$trip_detail = $this->webservices_model->gettrip($trip_id);
					// condition for time
					$trip_city_detail = $this->webservices_model->get_one_city_details_byid($trip_detail['iCityId']);

					/*$datetime2 = new DateTime($trip_detail['dBookingDate']);
					$datetime1 = new DateTime("now", new DateTimeZone($trip_city_detail['vTimeZone']));
					$diff = $datetime1->diff($datetime2);*/
					//-------------------------
					$bookTime = new DateTime($trip_detail['dBookingDate']);
					$driverTime = new DateTime('now', new DateTimeZone($trip_city_detail['vTimeZone']));
					$acceptTime = $driverTime->format('Y-m-d H:i:s');
					$compare = new DateTime($acceptTime);
					$diff = $compare->diff($bookTime);
					//-------------------------
					/*$this->printthis("Diff");
					$this->printthisexit($diff->i);*/
					if ($diff->i < 3 ) {
						// $trip_detail['dBookingDate']
						if ($trip_detail['eStatus']=='Pending') {
							if($trip_detail['eDriverAssign']=='No'){
								$driver_details = $this->webservices_model->get_driver_details_by_id($driver_id);
								$mainarr['iTripId'] = $trip_id;                 

								$totalaffectrows = $this->webservices_model->change_trip_status_to_complete_byid($trip_id);
								$totalaffectrows = $this->webservices_model->update_trip_details($trip_id,array('iVehicleOwnerId'=>$driver_details['iVehicleOwnerId']));
								$mainarr['iDriverId'] = $driver_id;

								$rider_id = $this->webservices_model->get_rider_id_from_trip_id($trip_id);
								$rider_details = $this->webservices_model->get_rider_device_details_by_id($rider_id);
								
								
								// code for sending push notification
								if($rider_details){
									$fullname = $driver_details['vFirstName']." ".$driver_details['vLastName'];
									$pushNotificationData['action'] = 'sendNotification';
									/*$pushNotificationData['msg'] = "Your Booking Request Accepted By ".$fullname." || ".$trip_id;*/
									$pushNotificationData['msg'] = $driver_details['vFirstName']." has accepted the Booking || ".$trip_id;
									$pushNotificationData['vDeviceid'] = $rider_details['device_id'];
									$pushNotificationData['eUserType'] = "Rider";
									$datapush = $this->pushNotification($pushNotificationData);
								}
								// end of code for sending push notification

								// send accept trip notification to driver partner
								$all_driver_trip_partners = $this->webservices_model->get_driver_partner_details_for_trip($driver_id,$trip_id);
								if(count($all_driver_trip_partners)>0){
									foreach ($all_driver_trip_partners as $keypartner => $valuepartner) {
										$fullname = $driver_details['vFirstName']." ".$driver_details['vLastName'];
										$pushNotificationData['action'] = 'sendNotification';
										/*$pushNotificationData['msg'] = "Trip Request From: ".$valuepartner['vPickupLocation']." To: ".$valuepartner['vDestinationLocation']." Accepted By ".$fullname." ? ".$trip_id;*/
										$pushNotificationData['msg'] = "Trip Accepted By ".$fullname." ? ".$trip_id;
										$pushNotificationData['vDeviceid'] = $valuepartner['device_id'];
										$pushNotificationData['eUserType'] = "Driver";
										$datapushpartner = $this->pushNotification($pushNotificationData);   
									}
								}
								// end of code for send accept trip notification to driver partner


								$all_driver_trips = $this->webservices_model->get_all_trip_driver_by_tripid($trip_id);
								if(count($all_driver_trips)>0){
									for($i=0;$i<count($all_driver_trips);$i++){
										if($all_driver_trips[$i]['iDriverId']!=$driver_id){
											$tmp_driver_id = $all_driver_trips[$i]['iDriverId'];
											$totalrows = $this->webservices_model->delete_trip_driver_by_id($tmp_driver_id,$trip_id);
										}
									}
								}
								//************* Additional Discount *************
								if ($driver_details['eAvailability']!='Local' && $trip_detail['eTripLocation']=='Outstation' && $trip_detail['eTripType']=='One Way') {
									$source_city = $this->GetCityFromAddressOrLatLong($trip_detail['vPickupLocation'],'address');
									$dest_city = $this->GetCityFromAddressOrLatLong($trip_detail['vDestinationLocation'],'address');
									$outst = $this->webservices_model->checkOutstationForDiscount($driver_id,$trip_detail['dTripDate'],$source_city,$dest_city);
									if (!empty($outst)) {
										$new_data['iAdditionalDriverDiscount'] = $outst['iAdditionalDiscount'];
	                            		$new_data['iOutOfStationId'] = $outst['iOutOfStationId'];
	                            		$resrows = $this->webservices_model->update_trip_details($trip_id,$new_data);
									}
								}
								//************* Additional Discount ends *************
								$status = $this->webservices_model->check_trip_and_driver($mainarr);
								if($status=='not exist'){
									$this->webservices_model->add_trip_and_driver($mainarr);
									
									
									$data['msg'] = 'Success';
								}
								else {
									$data['msg'] = 'Success';       
								}
							} // end of if
							else {
								$data['msg'] = 'Driver Already Assign To Trip'; 
							}
						} else {
							$data['msg'] = 'Trip has been canceled'; 
						}
					}else{
						$this->webservices_model->reject_driver_trip($driver_id,$trip_id);
						$data['msg'] = 'The trip is no longer available';
					}
				}else {
					$data['msg'] = 'Driver Not Exist';  
				}
			}else {
				$data['msg'] = 'Trip Not Exist';
			}
		}
		else {
			$data['msg'] = 'Failure';
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetFixRideCity(){
		$fixType = $_REQUEST['fixType'];
		if($fixType != ''){
			if($fixType=='Taxi'){
				$eFaretype = array('Local','OutStation');
				$eTripType = 'One Way';
			}else if ($fixType=='Local'){
				// Round, Local
				$eFaretype = array('Local');
				$eTripType = 'Round';
			}else if ($fixType=='OutStation'){
				// Round, Out
				$eFaretype = array('OutStation');
				$eTripType = 'Round';
			}else if ($fixType=='Shuttle'){
				// Round, Out
				$eFaretype = array('Shuttle');
				$eTripType = 'One Way';
			}

			else{
				$Data['msg'] = "Please Select Fix Ride Type";
				echo json_encode($Data);exit;
			}
			
			$getCityArr=$this->webservices_model->get_cities_of_fix_ride($eTripType,$eFaretype);
			if(count($getCityArr)>0){
				foreach ($getCityArr as $ctr => $city) {
					$resArr[$ctr]['iCityId'] = $city['iCityId'];
					$resArr[$ctr]['vCity'] = $city['vCity'].", ".$city['vState'].", ".$city['vCountry'];
				}
				$Data['data'] = $resArr;
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "No Fix Ride Available";
			}
		}else{
			$Data['msg'] = "Please Select Fix Ride Type";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetFixRideFromAddress(){
		$iCityId = $_REQUEST['iCityId'];
		$fixType = $_REQUEST['fixType'];
		if($iCityId != '' && $fixType != ''){
			if($fixType=='Taxi'){
				$eFaretype = array('Local','OutStation');
				$eTripType = 'One Way';
			}else if ($fixType=='Local'){
				// Round, Local
				$eFaretype = array('Local');
				$eTripType = 'Round';
			}else if ($fixType=='OutStation'){
				// Round, Out
				$eFaretype = array('OutStation');
				$eTripType = 'Round';
			}else if ($fixType=='Shuttle'){
				$eFaretype = array('Shuttle');
				$eTripType = 'One Way';
			}else{
				$Data['msg'] = "Please Select Fix Ride Type";
				echo json_encode($Data);exit;
			}
			//----------------
			$getFromAdd=$this->webservices_model->getPickUpLocationByCity($iCityId,$eTripType,$eFaretype);
			if(count($getFromAdd)>0){
				$Data['data']=$getFromAdd;
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "No Fix Ride Available For This City";
			}
		}else{
			$Data['msg'] = "Please Enter City";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetFixRideToAddress(){
		$iCityId = $_REQUEST['iCityId'];
		$fixType = $_REQUEST['fixType'];
		$vPickUpLocation = $_REQUEST['vPickUpLocation'];
		if($iCityId != '' && $vPickUpLocation!='' && $fixType!=''){
			if($fixType=='Taxi'){
				$eFaretype = array('Local','OutStation');
				$eTripType = 'One Way';
			}else if ($fixType=='Local'){
				// Round, Local
				$eFaretype = array('Local');
				$eTripType = 'Round';
			}else if ($fixType=='OutStation'){
				// Round, Out
				$eFaretype = array('OutStation');
				$eTripType = 'Round';
			}else if ($fixType=='Shuttle'){
				$eFaretype = array('Shuttle');
				$eTripType = 'One Way';
			}else{
				$Data['msg'] = "Please Select Fix Ride Type";
				echo json_encode($Data);exit;
			}
			$getToAdd=$this->webservices_model->getDropLocationByCityPickUp($iCityId,$vPickUpLocation,$eTripType,$eFaretype);
			if(count($getToAdd)>0){
				$Data['data']=$getToAdd;
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "No Fix Ride Available";
			}
		}else{
			$Data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetFilteredFixRideDetails(){
		$iCityId = $_REQUEST['iCityId'];
		$vPickUpLocation = $_REQUEST['vPickUpLocation'];
		$vDropLocation = $_REQUEST['vDropLocation'];
		$fixType = $_REQUEST['fixType'];
		//$eTripType = $_REQUEST['eTripType']; // One Way, Round
		if($iCityId != '' && $vPickUpLocation!='' && $vDropLocation!='' && $fixType!=''){
			if($fixType=='Taxi'){
				$eFaretype = array('Local','OutStation');
				$eTripType = 'One Way';
			}else if ($fixType=='Local'){
				// Round, Local
				$eFaretype = array('Local');
				$eTripType = 'Round';
			}else if ($fixType=='OutStation'){
				// Round, Out
				$eFaretype = array('OutStation');
				$eTripType = 'Round';
			}else if ($fixType=='Shuttle'){
				// Round, Out
				$eFaretype = array('Shuttle');
				$eTripType = 'One Way';
			}else{
				$Data['msg'] = "Please Select Fix Ride Type";
				echo json_encode($Data);exit;
			}
			$getFixRide=$this->webservices_model->getFilteredFixRide($iCityId,$vPickUpLocation,$vDropLocation,$eTripType,$eFaretype);
			$iVehicleCompanyId=$this->webservices_model->getShuttleID();
			if(count($getFixRide)>0){
				$getAllCarType=$this->webservices_model->allCarType();
				foreach ($getFixRide as $i => $value) {
					$getFixRide[$i]['RatesByCar']=array();
					$availableDrivers = 0;
					if ($value['eFaretype']=='Local') {
						$ridelocationArr = array('Local','Both');
					}else if($value['eFaretype']=='Shuttle'){
						$ridelocationArr = array('Shuttle');
					}else{
						$ridelocationArr = array('Outstation','Both');
					}
					$lat_long = explode('|', $value['tCityLatLong']);
					$qurVar = ($value['eRadiusUnit']=='KMs') ? 6371 : 3959;
					$getFixRide[$i]['fDistance'] = $getFixRide[$i]['fDistance']." ".$getFixRide[$i]['eRadiusUnit'];
					$getFixRide[$i]['fDuration'] = $this->ConvertHrToMin($value['fDuration']);
					if($getFixRide[$i]['eFaretype']=='Shuttle'){
						$drivers = $this->webservices_model->get_all_drivers_within_city($lat_long[0],$lat_long[1],$value['fRadius'],$qurVar,$ridelocationArr,$iVehicleCompanyId);
						$availableDrivers += count($drivers);
						unset($drivers);
						unset($tmpArr);
					}else{
						foreach ($getAllCarType as $j => $carType) {	
							$ratesByCarType=$this->webservices_model->rateByCar($value['iRideId'],$carType['iVehicleCompanyId']);
							if ($ratesByCarType['fTotalPrice'] > 0) {
								$tmpArr['iVehicleCompanyId']=$carType['iVehicleCompanyId'];
								$tmpArr['vCompany']=$carType['vCompany'];
								$tmpArr['vCarimage']=$carType['vCarimage'];
								$tmpArr['iRateId']=$ratesByCarType['iRateId'];
								$fTotalPrice = $ratesByCarType['fTotalPrice'] + ($ratesByCarType['fTotalPrice'] * ($getFixRide[$i]['fServicetax']/100));
								$tmpArr['fTotalPrice']=$value['vCurrencySymbol'].number_format($fTotalPrice,2);
								// $tmpArr['fTotalPrice']=$value['vCurrencySymbol'].number_format($ratesByCarType['fTotalPrice'],2);
								$image = $this->data['base_upload'].'car/'.$carType['iVehicleCompanyId'];
								if(file_exists($image)){
									$tmpArr['Image_Url']='Image Not Found.';
								}else{
									$tmpArr['Image_Url']=$this->data['base_url'].'uploads/car/'.$carType['iVehicleCompanyId'].'/'.$carType['vCarimage'];
								}
								$getFixRide[$i]['RatesByCar'][]=$tmpArr;
								$drivers = $this->webservices_model->get_all_drivers_within_city($lat_long[0],$lat_long[1],$value['fRadius'],$qurVar,$ridelocationArr,$carType['iVehicleCompanyId']);
								$availableDrivers += count($drivers);
								unset($drivers);
								unset($tmpArr);
							}
						}
					}
					if ($availableDrivers > 0) {
						$getFixRide[$i]['driverAvailable']='Yes';
					} else {
						$getFixRide[$i]['driverAvailable']='No';
					}
				}
				$Data['data']=$getFixRide;
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "No Fix Ride Available";
			}
		}else{
			$Data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	// *********************** Functions for Vehical Owner ***********************
	function vehicalOwnerLogin_21_11_2016($vEmail,$vPassword){
		$base_upload=$this->data['base_upload'];
		$base_path = $this->data['base_path'];
		if(($vEmail != '') && ($vPassword != '')){
			$owneremail = $this->webservices_model->check_Exist('vEmail',$vEmail,'vehicle_owner');
			if($owneremail){
				$ownerpassword = $this->webservices_model->check_password($vEmail,$vPassword,'vehicle_owner');
				if($ownerpassword){
					$ownerID = $this->webservices_model->check_owner_auth($vEmail,$vPassword);
					if($ownerID){
						$ownerdetail = $this->webservices_model->getOwnerDetailbyId($ownerID['iVehicleOwnerId']);
						
						$file_path = $base_path.'uploads/vehicle_owner/'.$ownerdetail['iVehicleOwnerId'].'/'.$ownerdetail['vProfile'];
						if (file_exists($file_path)) {
							$image_Url = $base_upload.'vehicle_owner/'.$ownerdetail['iVehicleOwnerId'].'/'.$ownerdetail['vProfile'];
						}else{
							$image_Url = $base_upload.'plash-holder.png';
						}
						$ownerData['iVehicleOwnerId'] = $ownerdetail['iVehicleOwnerId'];
						$ownerData['vFirstName'] = $ownerdetail['vFirstName'];
						$ownerData['vLastName'] = $ownerdetail['vLastName'];
						$ownerData['fullname'] = $ownerdetail['vFirstName'].' '.$ownerdetail['vLastName'];
						$ownerData['vEmail'] = $ownerdetail['vEmail'];
						$ownerData['vMobileNo'] = $ownerdetail['vMobileNo'];
						$ownerData['eStatus'] = $ownerdetail['eStatus'];
						$ownerData['vProfile'] = $ownerdetail['vProfile'];
						$ownerData['image_Url'] = $image_Url;
						$ownerData['tBusinessName'] = $ownerdetail['tBusinessName'];
						$ownerData['eBusinessType'] = $ownerdetail['eBusinessType'];
						$ownerData['vOtherBusinessType'] = $ownerdetail['vOtherBusinessType'];
						$ownerData['tAddress'] = ($ownerdetail['tAddress']=='')?'':$ownerdetail['tAddress'];
						$ownerData['iCountryId'] = ($ownerdetail['iCountryId'])?$ownerdetail['iCountryId']:"";
						$ownerData['vCountry'] = ($ownerdetail['vCountry'])?$ownerdetail['vCountry']:"";
						$ownerData['iStateId'] = ($ownerdetail['iStateId'])?$ownerdetail['iStateId']:"";
						$ownerData['vState'] = ($ownerdetail['vState'])?$ownerdetail['vState']:"";
						$ownerData['iCityId'] = ($ownerdetail['iCityId'])?$ownerdetail['iCityId']:"";
						$ownerData['vCity'] = ($ownerdetail['vCity'])?$ownerdetail['vCity']:"";
						$ownerData['vPostalCode'] = ($ownerdetail['vPostalCode'])?$ownerdetail['vPostalCode']:"";
						$ownerData['eOutOfService'] = $ownerdetail['eOutOfService'];
						$Data['data'] = $ownerData;
						$Data['msg']  = "Login Successfully";
						$Data['role'] = "owner";
					}else{
						$Data['msg'] = "Your status isn't active."; 
					}
				}else{
					$Data['msg'] = "Your password doesn't match.";
				}
			}else{
				$Data['msg'] = "Your email doesn't match.";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function autoOwnerLogin($id){
		$base_upload=$this->data['base_upload'];
		if($id){
			$ownerInfo = $this->webservices_model->getOwnerDetailbyId($id);
			if ($ownerInfo) {
				$base_path = $this->data['base_path'];
				$file_path = $base_path.'uploads/vehicle_owner/'.$ownerInfo['iVehicleOwnerId'].'/'.$ownerInfo['vProfile'];
				if (file_exists($file_path)) {
					$image_Url = $base_upload.'vehicle_owner/'.$ownerInfo['iVehicleOwnerId'].'/'.$ownerInfo['vProfile'];
				}else{
					$image_Url = $base_upload.'plash-holder.png';
				}
				$ownerDetails['vFirstName'] = $ownerInfo['vFirstName'];
				$ownerDetails['vLastName'] = $ownerInfo['vLastName'];
				$ownerDetails['fullname'] = $ownerInfo['vFirstName'].' '.$ownerInfo['vLastName'];
				$ownerDetails['vEmail'] = $ownerInfo['vEmail'];
				$ownerDetails['eStatus'] = $ownerInfo['eStatus'];
				$ownerDetails['iMobileNo'] = $ownerInfo['vMobileNo'];
				$ownerDetails['image_Url']= $image_Url;
				$ownerDetails['vProfile'] = $ownerInfo['vProfile'];
				$ownerDetails['tBusinessName'] = $ownerInfo['tBusinessName'];
				$ownerDetails['eBusinessType'] = $ownerInfo['eBusinessType'];
				$ownerDetails['vOtherBusinessType'] = $ownerInfo['vOtherBusinessType'];
				$ownerDetails['tAddress'] = $ownerInfo['tAddress'];
				$ownerDetails['iCountryId'] = ($ownerInfo['iCountryId'])?$ownerInfo['iCountryId']:"";
				$ownerDetails['vCountry'] = ($ownerInfo['vCountry'])?$ownerInfo['vCountry']:"";
				$ownerDetails['iStateId'] = ($ownerInfo['iStateId'])?$ownerInfo['iStateId']:"";
				$ownerDetails['vState'] = ($ownerInfo['vState'])?$ownerInfo['vState']:"";
				$ownerDetails['iCityId'] = ($ownerInfo['iCityId'])?$ownerInfo['iCityId']:"";
				$ownerDetails['vCity'] = ($ownerInfo['vCity'])?$ownerInfo['vCity']:"";
				$ownerDetails['vPostalCode'] = ($ownerInfo['vPostalCode'])?$ownerInfo['vPostalCode']:"";
				$ownerDetails['eOutOfService'] = $ownerInfo['eOutOfService'];
				$Data['data'] = $ownerDetails;
				$Data['msg']  = "Login Successfully";
				$Data['role'] = "owner";
			}else{
				$Data['msg'] = "No Record Found";
			}
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function OwnerRegister_19_11_2016(){
		$vEmail = $this->input->post('vEmail');
		if($vEmail != '' && $this->input->post('fullname')!='' && $this->input->post('tBusinessName')!='' && $this->input->post('tAddress')!='' && $this->input->post('eBusinessType')!='' && $this->input->post('vPassword')!='' && $this->input->post('vPostalCode')!='' && $this->input->post('iCountryId')!='' && $this->input->post('iStateId')!='' && $this->input->post('iCityId')!='' && $this->input->post('vMobileNo1')!=''){
			$check_owner_exists = $this->webservices_model->check_email_exists('vehicle_owner',$vEmail);
			$check_client_exists = $this->webservices_model->check_email_exists("client",$vEmail);
			$check_driver_exists = $this->webservices_model->check_email_exists("driver",$vEmail);
			
			if($check_driver_exists==0 && $check_client_exists==0 && $check_owner_exists==0){
				$typeArr = array('Individual','Partnership','Company','Other');
				$fullname = explode(" ",$this->input->post('fullname'));
				$ownerData['vFirstName'] = $fullname[0];
				if ($fullname[1]) {
					$ownerData['vLastName'] = $fullname[1];
				}
				/*$ownerData['vFirstName']=$this->input->post('vFirstName');
				$ownerData['vLastName']=$this->input->post('vLastName');*/
				$ownerData['tBusinessName']=$this->input->post('tBusinessName');
				if (in_array($this->input->post('eBusinessType'), $typeArr)) {
					if ($this->input->post('eBusinessType')=='Other') {
						if ($this->input->post('vOtherBusinessType')!='') {
							$ownerData['eBusinessType']=$this->input->post('eBusinessType');
							$ownerData['vOtherBusinessType']=$this->input->post('vOtherBusinessType');
						} else {
							$data['msg'] = "Error";
							header('Content-type: application/json');
							$callback = '';
							if (isset($_REQUEST['callback'])){
								$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
							}
							$main = json_encode($data);
							echo $callback . ''.$main.'';
							exit; 
						}
					} else {
						$ownerData['eBusinessType']=$this->input->post('eBusinessType');
					}
				} else {
					$data['msg'] = "Error";
					header('Content-type: application/json');
					$callback = '';
					if (isset($_REQUEST['callback'])){
						$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
					}
					$main = json_encode($data);
					echo $callback . ''.$main.'';
					exit; 
				}
				
				$ownerData['tAddress']=$this->input->post('tAddress');
				$ownerData['iCountryId']=$this->input->post('iCountryId');
				$ownerData['iStateId']=$this->input->post('iStateId');
				$ownerData['iCityId']=$this->input->post('iCityId');
				$ownerData['vMobileNo1']=$this->input->post('vMobileNo1');
				$ownerData['vEmail']=$vEmail;
				$password = $this->input->post('vPassword');
				$ownerData['vPassword']=encrypt($password);
				$ownerData['vPostalCode']=$this->input->post('vPostalCode');
				$ownerData['eStatus']='Inactive';
				$ownerData['vActivationCode']=rand_str();
				$ownerData['dRegisterDate'] = date('Y-m-d');
				$iVehicleOwnerId = $this->webservices_model->save_data($ownerData,'vehicle_owner');
				if ($iVehicleOwnerId) {
					if($_FILES['vProfile']['name']!=''){
						$size=array();
						$size['width']='57';
						$size['height']='57';
						$size['width2']='228';
						$size['height2']='228';
						$image_uploaded =$this->do_upload_img($iVehicleOwnerId,'vehicle_owner','vProfile',$size);
						$user['vProfile'] = $image_uploaded ;
						$ownerImage = $this->webservices_model->update_owner_detail($user,$iVehicleOwnerId);
					}
				
					// Send Email
					$siteurl = $this->config->item('base_url');
					$MailFooter = $this->data['MAIL_FOOTER'];
					$adminEmailId = $this->data['EMAIL_ADMIN'];
					$siteName = $this->data['SITE_NAME'];
					$name = ucfirst($ownerData['vFirstName']). ' ' . ucfirst($ownerData['vLastName']);

					$image = $siteurl;
					$link=$this->data['site_url'].'login/confirm_email?code='.$ownerData['vActivationCode']."&role=vehicle_owner";
					$bodyArr = array("#NAME#", "#PASSWORD#", "#EMAIL#", "#SITEURL#", "#MAILFOOTER#", "#SITE_NAME#", "#FIRSTNAME#", "#LASTNAME#", "#IMAGE_URL#","#LINK#");
					$postArr = array($name, $password, $vEmail, $siteurl, $MailFooter, $siteName, ucfirst($ownerData['vFirstName']), ucfirst($ownerData['vLastName']), $image,$link);
					$sendClient = $this->Send("NEW_OWNER_REGISTER", "Client", $vEmail, $bodyArr, $postArr);

					// as Driver
					//---------------------------------------
					$driverData['iVehicleOwnerId']=$iVehicleOwnerId;
					$driverData['vFirstName']=$ownerData['vFirstName'];
					$driverData['vLastName']=$ownerData['vLastName'];
					$driverData['vEmail']=$vEmail;
					$driverData['vBusinessName']=$ownerData['vFirstName'];
					$driverData['tAddress']=$ownerData['tAddress'];
					$driverData['iCountryId']=$ownerData['iCountryId'];
					$driverData['iStateId']=$ownerData['iStateId'];
					$driverData['iCityId']=$ownerData['iCityId'];
					$driverData['vZipcode']=$ownerData['vPostalCode'];
					$driverData['iMobileNo1']=$ownerData['vMobileNo1'];
					$driverData['eAvailability']='CarPoolBoth';
					$driverData['eSmokingPreference']='Does Not Matter';
					$driverData['eGenderPreference']='Does Not Matter';
					$driverData['dRegisterDate'] = date('Y-m-d');
					$driverData['vActivationCode'] = random_string('alnum',8);
					$driverData['eStatus'] = 'Active';
					$driverPassword=$password;
					$driverData['vPassword'] = $this->encrypt($driverPassword);
					$iDriverId = $this->webservices_model->save_data($driverData,'driver');
					if ($_FILES['vProfile']['name'] != '') {
						$clean_name = $this->clean($_FILES['vProfile']['name']);
						$img_uploaded = $this->do_upload_driver_profile_photo($iDriverId,$clean_name,'vProfileImage');
						$res = $this->webservices_model->update_driver_detail(array('vProfileImage' => $img_uploaded),$iDriverId);
					}
					// Send Mail
					$driverlink=$this->data['site_url'].'login/confirm_email?code='.$driverData['vActivationCode'];
					$driverBodyArr = array("#NAME#", "#PASSWORD#", "#EMAIL#", "#SITEURL#", "#MAILFOOTER#", "#SITE_NAME#", "#LINK#", "#FIRSTNAME#", "#LASTNAME#", "#IMAGE_URL#");
					$driverPostArr = array($name, $password, $vEmail, $siteurl, $MailFooter, $siteName, $driverlink, ucfirst($ownerData['vFirstName']), ucfirst($ownerData['vLastName']), $image);
					$sendDriver = $this->Send("CREATE_DRIVER_BY_OWNER", "Driver", $vEmail, $driverBodyArr, $driverPostArr);
					
					//---------------------------------------
					$data['msg'] = "Registered Successfully";
					$data['iVehicleOwnerId']="$iVehicleOwnerId";
				}else{
					$data['msg'] = "Registeration Error";
				}
			}else{
			  $data['msg'] = "Email Address Exist";
			}
		}else{
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit; 
	}

	function getOwnerDetail(){
		$iVehicleOwnerId = $this->input->post('iVehicleOwnerId');
		if($iVehicleOwnerId != ''){
			$owner_exists = $this->webservices_model->check_owner_exists($iVehicleOwnerId);
			if ($owner_exists=='exist') {
				$ownerdetail = $this->webservices_model->getOwnerDetailbyId($iVehicleOwnerId);
				if ($ownerdetail) {
					if (strpos($ownerdetail['vMobileNo'], '-') !== false) {
					$iMobileNo=explode('-',$ownerdetail['vMobileNo']);
					$ownerdetail['vMobileNoCode']=$iMobileNo[0];
					$ownerdetail['vMobileNo']=$iMobileNo[1];
					}else{
						$ownerdetail['vMobileNoCode']="";
					}
					if (strpos($ownerdetail['vWorkPhoneNo'], '-') !== false) {
						$iMobileNo=explode('-',$ownerdetail['vWorkPhoneNo']);
						$ownerdetail['vWorkPhoneNoCode']=$iMobileNo[0];
						$ownerdetail['vWorkPhoneNo']=$iMobileNo[1];
					}else{
						$ownerdetail['vWorkPhoneNoCode']="";
					}

					$base_upload=$this->data['base_upload'];
					$base_path = $this->data['base_path'];
					$file_path = $base_path.'uploads/vehicle_owner/'.$iVehicleOwnerId.'/'.$ownerdetail['vProfile'];
					if ($ownerdetail['vProfile']=='') {
						$ownerdetail['image_Url'] = $base_upload.'plash-holder.png';
					} else {
						if (file_exists($file_path)){
							$ownerdetail['image_Url'] = $base_upload.'vehicle_owner/'.$iVehicleOwnerId.'/'.$ownerdetail['vProfile'];
						}else{
							$ownerdetail['image_Url'] = $base_upload.'plash-holder.png';
						}
					}
					$ownerdetail['fullname']= $ownerdetail['vFirstName'].' '.$ownerdetail['vLastName'];
					$data['data'] = $ownerdetail;
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "Vehical Owner Account Is Inactive";
				}
			} else {
				$data['msg'] = "Vehical Owner Not Exist";
			}
		}else{
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function EditOwnerDetail(){
		$iVehicleOwnerId = $this->input->post('iVehicleOwnerId');
		if($iVehicleOwnerId != '' && trim($this->input->post('fullname'))!='' && trim($this->input->post('vEmail'))!=''){
			$owner_exists = $this->webservices_model->check_owner_exists($iVehicleOwnerId);
			if ($owner_exists=='exist') {
				$ownerdetail = $this->webservices_model->getOwnerDetailbyId($iVehicleOwnerId);
				
				// Name
				$fullname = explode(" ",trim($this->input->post('fullname')));
				$newDetail['vFirstName'] = $fullname[0];
				$newDetail['vLastName'] = $fullname[1];
				
				// Email
				$vEmail = trim($this->input->post('vEmail'));
				if ($vEmail != $ownerdetail['vEmail']) {
					$check_owner_exists = $this->webservices_model->check_email_exists('vehicle_owner',$vEmail);
					$check_client_exists = $this->webservices_model->check_email_exists("client",$vEmail);
					$check_driver_exists = $this->webservices_model->check_email_exists("driver",$vEmail);
					
					if($check_driver_exists==0 && $check_client_exists==0 && $check_owner_exists==0){
						$newDetail['vEmail']=$vEmail;
					}else{
						$data['msg'] = "Email Address Exist";
						header('Content-type: application/json');
						$callback = '';
						if (isset($_REQUEST['callback'])){
							$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
						}
						$main = json_encode($data);
						echo $callback . ''.$main.'';
						exit;
					}
				}
				// Password
				if ($this->input->post('vPassword')!='') {
					$newDetail['vPassword']=encrypt($this->input->post('vPassword'));
				}
				
				// Business Type
				if ($this->input->post('eBusinessType')!='') {
					$typeArr = array('Individual','Partnership','Company','Other');
					if (in_array($this->input->post('eBusinessType'), $typeArr)) {
						if ($this->input->post('eBusinessType')=='Other') {
							if ($this->input->post('vOtherBusinessType')!='') {
								$newDetail['eBusinessType']=$this->input->post('eBusinessType');
								$newDetail['vOtherBusinessType']=$this->input->post('vOtherBusinessType');
							} else {
								$data['msg'] = "Error";
								header('Content-type: application/json');
								$callback = '';
								if (isset($_REQUEST['callback'])){
									$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
								}
								$main = json_encode($data);
								echo $callback . ''.$main.'';
								exit; 
							}
						} else {
							$newDetail['eBusinessType']=$this->input->post('eBusinessType');
						}
					} else {
						$data['msg'] = "Error";
						header('Content-type: application/json');
						$callback = '';
						if (isset($_REQUEST['callback'])){
							$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
						}
						$main = json_encode($data);
						echo $callback . ''.$main.'';
						exit; 
					}
				}
				if ($this->input->post('tBusinessName')!='') {
					$newDetail['tBusinessName'] = $this->input->post('tBusinessName');
				}
				if ($this->input->post('tAddress')) {
					$newDetail['tAddress'] = $this->input->post('tAddress');
				}
				if($this->input->post('iCountryId')){
					$newDetail['iCountryId']=$this->input->post('iCountryId');
				}
				if($this->input->post('iStateId')){
					$newDetail['iStateId']=$this->input->post('iStateId');
				}
				if($this->input->post('iCityId')){
					$newDetail['iCityId']=$this->input->post('iCityId');
				}
				if($this->input->post('vPostalCode')){
					$newDetail['vPostalCode']=$this->input->post('vPostalCode');
				}   
				if ($this->input->post('vMobileNo')) {
					$newDetail['vMobileNo1'] = $this->input->post('vMobileNo');
				}
				if ($this->input->post('vWorkPhoneNo')) {
					$newDetail['vWorkPhoneNo1'] = $this->input->post('vWorkPhoneNo');
				}

				// if vProfile
				if($_FILES['vProfile']['name']!=''){
					// Remove Previous
					$deletepath = $this->data['base_path'].'uploads/vehicle_owner/'.$iVehicleOwnerId.'/*';
					$files = glob($deletepath);
					foreach($files as $file){ 
						if(is_file($file))
						unlink($file);
					}
					// upload new
					$size=array();
					$size['width']='57';
					$size['height']='57';
					$size['width2']='228';
					$size['height2']='228';
					$image_uploaded =$this->do_upload_img($iVehicleOwnerId,'vehicle_owner','vProfile',$size);
					$newDetail['vProfile'] = $image_uploaded ;
				}
				// update record
				$res = $this->webservices_model->update_owner_detail($newDetail,$iVehicleOwnerId);
				$ownerNewDetail = $this->webservices_model->getOwnerDetailbyId($iVehicleOwnerId);
				$base_upload=$this->data['base_upload'];
				$base_path = $this->data['base_path'];
				$file_path = $base_path.'uploads/vehicle_owner/'.$iVehicleOwnerId.'/'.$ownerNewDetail['vProfile'];
				if ($ownerNewDetail['vProfile']=='') {
					$ownerNewDetail['image_Url'] = $base_upload.'plash-holder.png';
				} else {
					if (file_exists($file_path)){
						$ownerNewDetail['image_Url'] = $base_upload.'vehicle_owner/'.$iVehicleOwnerId.'/'.$ownerNewDetail['vProfile'];
					}else{
						$ownerNewDetail['image_Url'] = $base_upload.'plash-holder.png';
					}
				}
				$ownerNewDetail['fullname']= $ownerNewDetail['vFirstName'].' '.$ownerNewDetail['vLastName'];
				$data['data'] = $ownerNewDetail;
				$data['msg'] = "Success";
			} else {
				$data['msg'] = "Vehical Owner Not Exist";
			}
		}else{
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function OwnerTripDetail(){
		$iTripId = $this->input->post('iTripId');
		if($iTripId != ''){
			$trip_status = $this->webservices_model->check_trip_exists($iTripId);
			if($trip_status=='exist'){
				$base_upload=$this->data['base_upload'];
				$base_path = $this->data['base_path'];
				$tripArr = $this->webservices_model->getTripDetailForOwner($iTripId);
				if($tripArr['eTripLocation']=='OutStationByDuration'){
					$tripArr['fDistancefare'] = $tripArr['fPerMileFare']*$tripArr['fDistance'];
					$tripArr['fDistancefare'] = $mycurrency['vCurrencySymbol'].$tripArr['fDistancefare'];
				}else{
					$tripArr['fDistancefare'] = $mycurrency['vCurrencySymbol'].$tripArr['fPerMileFare'];
				}
				$tripArr['clientFullName'] = $tripArr['ClientFirstName'].' '.$tripArr['ClientLastName'];
				$tripArr['driverFullName'] = $tripArr['vFirstName'].' '.$tripArr['vLastName'];
				//$tripArr['fMinimumKm']=$this->webservices_model->getOutstationBydurationMinkmMiles($tripArr['iVehicleCompanyId'],$tripArr['iCityId']);

				$fare_details=$this->webservices_model->getdurationFulDetails($tripArr['iVehicleCompanyId'],$tripArr['iCityId'],$tripArr['eTripLocation']);
				$tripArr['fMinimumKm']=$fare_details['fMinimumKm'];
				$fare_details['lOptions']=explode(',',$fare_details['lOptions']);
				foreach ($fare_details['lOptions'] as $key => $value) {
					$fare=explode('|', $value);
					if($fare[0]==$tripArr['vRoundOption']){
						$tripArr['Fare']=$mycurrency['vCurrencySymbol'].$fare[1];
						$tripArr['Option']=$fare[0];
					}
				}

				if($tripArr['ClientProfileImage']==''){
					$tripArr['client_image_url'] = $base_upload.'plash-holder.png';
				}else{
					$driver_image_path = $base_path.'uploads/client/'.$tripArr['iClientId'].'/'.$tripArr['ClientProfileImage'];
					if (file_exists($driver_image_path)){
						$tripArr['client_image_url'] = $base_upload.'client/'.$tripArr['iClientId'].'/'.$tripArr['ClientProfileImage'];
					}else{
						$tripArr['client_image_url'] = $base_upload.'plash-holder.png';
					}
				}
				if ($tripArr['vProfileImage']=='') {
					$tripArr['driver_image_url'] = $base_upload.'plash-holder.png';
				} else {
					$driver_image_path = $base_path.'uploads/driver/'.$tripArr['iDriverId'].'/'.$tripArr['vProfileImage'];
					if (file_exists($driver_image_path)){
						$tripArr['driver_image_url'] = $base_upload.'driver/'.$tripArr['iDriverId'].'/'.$tripArr['vProfileImage'];
					}else{
						$tripArr['driver_image_url'] = $base_upload.'plash-holder.png';
					}
				}
				$pickUpLatLong = explode('|', $tripArr['tPickUpAddressLatLong']);
				$destLatLong = explode('|', $tripArr['tDestinationAddressLatLong']);
				$tripArr['pickup_latitude'] = $pickUpLatLong[0];
				$tripArr['pickup_longitude'] = $pickUpLatLong[1];
				$tripArr['dest_latitude'] = $destLatLong[0];
				$tripArr['dest_longitude'] = $destLatLong[1];

				$tripstarttime = date_create($tripArr['dTripDate']);
				$tripArr['dTripStartTime'] = date_format($tripstarttime, 'g:i A');

				if($tripArr['dRideEndDate']!='0000-00-00 00:00:00'){
					$tripendtime = date_create($tripArr['dRideEndDate']);
					$tripArr['dTripEndTime'] = date_format($tripendtime, 'g:i A');
				}
				else {
					$tripArr['dTripEndTime'] = "";
				}
				//Shuttle
				if ($tripArr['eTripLocation']=="Shuttle") {
					$shuttleInfo = explode('|', $tripArr['vShuttleInfo']);
					$shType=explode(',', $shuttleInfo[0]);
					$shFare=explode(',', $shuttleInfo[1]);
					// Adult
					$adultFare = $shType[0]*$shFare[0];
					$tripArr['adultFare'] = number_format($adultFare, 2);
					// Chield
					$childFare = $shType[1]*$shFare[1];
					$tripArr['childFare'] = number_format($childFare, 2);
					// Infant
					$infantFare = $shType[2]*$shFare[2];
					$tripArr['infantFare'] = number_format($infantFare, 2);
					// Pet
					$petFare = $shType[3]*$shFare[3];
					$tripArr['petFare'] = number_format($petFare, 2);
					// Bag
					$total_bags = ($shType[4] > 2) ? ($shType[4]-2): 0 ;
					$bagFare = $total_bags*$shFare[4];
					$tripArr['bagFare'] = number_format($bagFare, 2);
					// Hand Bag
					$total_hand_bags = ($shType[5] > 1) ? ($shType[5]-1): 0 ;
					$handBagFare = $total_hand_bags*$shFare[5];
					$tripArr['handBagFare'] = number_format($handBagFare, 2);
				} else {
					
				}
				
				unset($tripArr['vShuttleInfo']);
				unset($tripArr['ClientFirstName']);
				unset($tripArr['ClientLastName']);
				unset($tripArr['vFirstName']);
				unset($tripArr['vLastName']);
				unset($tripArr['vProfileImage']);
				unset($tripArr['ClientProfileImage']);
				// $this->printthisexitexit($tripArr);
				$data['data'] = $tripArr;
				$data['msg'] = "Success";
			}else{
				$data['msg'] = "Trip Not Exist";
			} 
		}else{
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function getList(){
		if($this->input->post('Module')=='Type'){
			//$AllData=$this->webservices_model->get_active_Records('vehicle_companies');

			$iVehicleOwnerId=$this->input->post('iVehicleOwnerId');
			$iCountryId=$this->webservices_model->getOwnerCountry($iVehicleOwnerId);
			$AllData=$this->webservices_model->get_vehiclecompany($iCountryId['iCountryId']);
			$data['data'] = $AllData;
			$data['msg'] = "Success";
		}elseif($this->input->post('Module')=='Model' && $this->input->post('iVehicleCompanyId')){
			$iVehicleCompanyId=$this->input->post('iVehicleCompanyId');
			$AllData=$this->webservices_model->get_active_Records('vehicle_models','iVehicleCompanyId',$iVehicleCompanyId);
			$data['data'] = $AllData;
			$data['msg'] = "Success";
		}elseif($this->input->post('Module')=='Color'){
			$AllData=$this->webservices_model->get_active_Records('color_master');
			$data['data'] = $AllData;
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function AllVehicleActions(){
		if($this->input->post('Module')=='Add'){
			if( $this->input->post('iModelId') && $this->input->post('iVehicleOwnerId') && $this->input->post('iColorId') && $this->input->post('vRegistrationNo') && $this->input->post('eAirConditioned')){
				$vehicle_data['eStatus']='Not Approved';
				$vehicle_data['iModelId']=$this->input->post('iModelId');
				$vehicle_data['iVehicleOwnerId']=$this->input->post('iVehicleOwnerId');
				$vehicle_data['iColorId']=$this->input->post('iColorId');
				$vehicle_data['vRegistrationNo']=$this->input->post('vRegistrationNo');
				$vehicle_data['eAirConditioned']=$this->input->post('eAirConditioned');
				$iVehicleAttributeId=$this->webservices_model->insert_vehicle($vehicle_data);
				$owner=$this->webservices_model->getOwner_details($this->input->post('iVehicleOwnerId'));
					$msg=$owner['vFirstName'].' '.$owner['vLastName']."Has added new vehicle (".$vehicle_data['vRegistrationNo'].") details, Please check and varify";
				$this->SendEmailNotificationToAdmin($msg);
				$size=array();
				if($_FILES['tVehicleFrontImage']['name']!=''){
					$front_image =$this->do_upload_img($iVehicleAttributeId,'vehicleImage','tVehicleFrontImage',$size);
					$user['tVehicleFrontImage'] = $front_image ;
				}
				if($_FILES['tVehicleInsideImage']['name']!=''){
					$inside_image =$this->do_upload_img($iVehicleAttributeId,'vehicleImage','tVehicleInsideImage',$size);
					$user['tVehicleInsideImage'] = $inside_image ;
				}
				if($_FILES['tVehicleOwnerDocument']['name']!=''){
					$owner_doc=$this->do_upload_img($iVehicleAttributeId,'owner_document','tVehicleOwnerDocument',$size);
					$user['tVehicleOwnerDocument'] = $owner_doc ;
				}
				if($_FILES['tVehicleInsuranceDocument']['name']!=''){
					$ins_doc =$this->do_upload_img($iVehicleAttributeId,'insurance_document','tVehicleInsuranceDocument',$size);
					$user['tVehicleInsuranceDocument'] = $ins_doc;
				}
				$this->webservices_model->update_vehicle_docs($iVehicleAttributeId,$user);
				$data['msg'] = "Success";
			}else{
				$data['msg'] = "Failure";	
			}
		}elseif($this->input->post('Module')=='List'){
			if($this->input->post('iVehicleOwnerId')){
				$iVehicleOwnerId=$this->input->post('iVehicleOwnerId');
				$allList=$this->webservices_model->get_Owner_vehicle($iVehicleOwnerId);
				foreach ($allList as $key => $value) {
					if($allList[$key]['vFirstName']==null){
						$allList[$key]['vFirstName']='';
					}
					if($allList[$key]['vLastName']==null){
						$allList[$key]['vLastName']='';
					}
				}
				$data['data']=$allList;
				$data['msg'] = "Success";
			}else{
				$data['msg'] = "Failure";	
			}
		}elseif($this->input->post('Module')=='Delete'){
			if($this->input->post('iVehicleAttributeId')){
				$iVehicleAttributeId=$this->input->post('iVehicleAttributeId');
				$iDriverId = $this->webservices_model->get_driver_id_by_attribute_id($iVehicleAttributeId);
				if ($iDriverId != 0 ) {
					$driver=$this->webservices_model->getDriverAssigned($iVehicleAttributeId);
					if($driver > 0){
						$data['msg'] = "Vehicle Is On Trip Can not be deleted";
						echo json_encode($data);
						exit;
					}
				}
				// Remove files
				$url1 = $this->data['base_path'] . 'uploads/owner_document/'.$iVehicleAttributeId.'/';
				array_map('unlink',glob($url1."/*"));
				rmdir($url1);
				$url2 = $this->data['base_path'] . 'uploads/insurance_document/'.$iVehicleAttributeId.'/';
				array_map('unlink',glob($url2."/*"));
				rmdir($url2);
				$url3 = $this->data['base_path'] . 'uploads/vehicleImage/'.$iVehicleAttributeId.'/';
				array_map('unlink',glob($url3."/*"));
				rmdir($url3);
				$this->webservices_model->delete_vehicle($iVehicleAttributeId);
				$data['msg'] = "Success";
			}else{
				$data['msg'] = "Failure";	
			}	
		}else if($this->input->post('Module')=='Edit'){
			if($this->input->post('iVehicleAttributeId') && $this->input->post('iModelId') && $this->input->post('iVehicleOwnerId') && $this->input->post('iColorId') && $this->input->post('vRegistrationNo') && $this->input->post('eAirConditioned')){
				$iVehicleAttributeId = $this->input->post('iVehicleAttributeId');
				$vehicalDetail = $this->webservices_model->getVehicalDetailById($iVehicleAttributeId);
				if ($vehicalDetail) {
					$vehicle_data['iModelId']=$this->input->post('iModelId');
					$vehicle_data['iVehicleOwnerId']=$this->input->post('iVehicleOwnerId');
					$vehicle_data['iColorId']=$this->input->post('iColorId');
					$vehicle_data['vRegistrationNo']=$this->input->post('vRegistrationNo');
					$vehicle_data['eAirConditioned']=$this->input->post('eAirConditioned');
					$vehicle_data['eStatus']="Not Approved";
					$size=array();
					$owner=$this->webservices_model->getOwner_details($this->input->post('iVehicleOwnerId'));
					$msg=$owner['vFirstName'].' '.$owner['vLastName']."Has Changed his vehicle (".$vehicle_data['vRegistrationNo'].") details, Please check and varify";
					$this->SendEmailNotificationToAdmin($msg);
					if($_FILES['tVehicleFrontImage']['name']!=''){
						// Remove Previous
						$crop_imag = array('image1'=>'228X228_','image2'=>'57X57_');
						$tableData['tablename'] = 'vehicle_attribute';
						$tableData['update_field'] = 'iVehicleAttributeId';
						$tableData['image_field'] = 'tVehicleFrontImage';
						$tableData['crop_image'] = $crop_imag;
						$tableData['folder_name'] ='vehicleImage';
						$tableData['field_id'] = $iVehicleAttributeId;
						$deleteImage = $this->delete_image($tableData);
						// Upload
						$image_name =$this->do_upload_img($iVehicleAttributeId,'vehicleImage','tVehicleFrontImage',$size);
						$vehicle_data['tVehicleFrontImage'] = $image_name;
					}
					if($_FILES['tVehicleInsideImage']['name']!=''){
						// Remove Previous
						$crop_imag = array('image1'=>'228X228_','image2'=>'57X57_');
						$tableData['tablename'] = 'vehicle_attribute';
						$tableData['update_field'] = 'iVehicleAttributeId';
						$tableData['image_field'] = 'tVehicleInsideImage';
						$tableData['crop_image'] = $crop_imag;
						$tableData['folder_name'] ='vehicleImage';
						$tableData['field_id'] = $iVehicleAttributeId;
						$deleteImage = $this->delete_image($tableData);
						// Upload
						$image_name =$this->do_upload_img($iVehicleAttributeId,'vehicleImage','tVehicleInsideImage',$size);
						$vehicle_data['tVehicleInsideImage'] = $image_name;
					}
					if($_FILES['tVehicleOwnerDocument']['name']!=''){
						// Remove Previous
						$url1 = $this->data['base_path'] . 'uploads/owner_document/'.$iVehicleAttributeId.'/';
						array_map('unlink',glob($url1."/*"));

						$size=array();
						$user['tVehicleOwnerDocument']=$_FILES['tVehicleOwnerDocument']['name'];             
						$image_uploaded =$this->do_upload_img($iVehicleAttributeId,'owner_document','tVehicleOwnerDocument',$size);
						$vehicle_data['tVehicleOwnerDocument'] = $image_uploaded ;
					}
					if($_FILES['tVehicleInsuranceDocument']['name']!=''){
						$url2 = $this->data['base_path'] . 'uploads/insurance_document/'.$iVehicleAttributeId.'/';
						array_map('unlink',glob($url2."/*"));

						$size=array();
						$user['tVehicleInsuranceDocument']=$_FILES['tVehicleInsuranceDocument']['name'];             
						$image_uploaded =$this->do_upload_img($iVehicleAttributeId,'insurance_document','tVehicleInsuranceDocument',$size);
						$vehicle_data['tVehicleInsuranceDocument'] = $image_uploaded;
					}
					$this->webservices_model->update_vehicle_docs($iVehicleAttributeId,$vehicle_data);
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "No Record Found";
				}
			}else{
				$data['msg'] = "Failure";	
			}
		}else{
			$data['msg'] = "Failure";	
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}
	
	function assignDriverToVehicle(){
		if($this->input->post('iVehicleAttributeId') && $this->input->post('iDriverId')){
			$this->webservices_model->assignDriver($this->input->post('iVehicleAttributeId'),$this->input->post('iDriverId'));
			// code for sending push notification to driver // 529c6add9f0c3905
			$driver_device_details = $this->webservices_model->get_driver_device_details($this->input->post('iDriverId'));
			if($driver_device_details){
				$pushNotificationData['action'] = 'sendNotification';
				$pushNotificationData['msg'] = "New Vehicle Assign To You.";
				$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
				$pushNotificationData['eUserType'] = "Driver";
				$datapush = $this->pushNotification($pushNotificationData);
			}	
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "Failure";	
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function UnAllocateVehicle(){
		if($this->input->post('iDriverId')){
			$this->webservices_model->remove_driver_vehicle($this->input->post('iDriverId'));
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "Failure";	
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function getFreeVehicleListingByVehicelOwner(){
		if($this->input->post('iVehicleOwnerId')){
			$freeVehicles=$this->webservices_model->getOwnerFreeVehicleList($this->input->post('iVehicleOwnerId'));
			$data['data']=$freeVehicles;
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "Failure";	
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;		
	}

	function getVehicalDetail(){
		if($this->input->post('iVehicleAttributeId')){
			$iVehicleAttributeId = $this->input->post('iVehicleAttributeId');
			$vehicalDetail = $this->webservices_model->getVehicalDetailById($iVehicleAttributeId);
			// $this->printthisexit($vehicalDetail);
			if ($vehicalDetail) {
				$base_upload=$this->data['base_upload'];
				$base_path = $this->data['base_path'];

				$insd_path = $base_path.'uploads/insurance_document/'.$iVehicleAttributeId.'/'.$vehicalDetail['tVehicleInsuranceDocument'];
				if (file_exists($insd_path)){
					$vehicalDetail['insurance_document_url'] = $base_upload.'insurance_document/'.$iVehicleAttributeId.'/'.$vehicalDetail['tVehicleInsuranceDocument'];
				}else{
					$vehicalDetail['insurance_document_url'] = '';
				}

				$od_path = $base_path.'uploads/owner_document/'.$iVehicleAttributeId.'/'.$vehicalDetail['tVehicleOwnerDocument'];
				if (file_exists($od_path)){
					$vehicalDetail['owner_document_url'] = $base_upload.'owner_document/'.$iVehicleAttributeId.'/'.$vehicalDetail['tVehicleOwnerDocument'];
				}else{
					$vehicalDetail['owner_document_url'] = '';
				}
				$fi_path = $base_path.'uploads/vehicleImage/'.$iVehicleAttributeId.'/'.$vehicalDetail['tVehicleFrontImage'];
				if (file_exists($fi_path)){
					$vehicalDetail['front_image_url'] = $base_upload.'vehicleImage/'.$iVehicleAttributeId.'/'.$vehicalDetail['tVehicleFrontImage'];
				}else{
					$vehicalDetail['front_image_url'] = '';
				}
				$in_path = $base_path.'uploads/vehicleImage/'.$iVehicleAttributeId.'/'.$vehicalDetail['tVehicleInsideImage'];
				if (file_exists($in_path)){
					$vehicalDetail['inside_image_url'] = $base_upload.'vehicleImage/'.$iVehicleAttributeId.'/'.$vehicalDetail['tVehicleInsideImage'];
				}else{
					$vehicalDetail['inside_image_url'] = '';
				}
				
				$data['data'] = $vehicalDetail;
				$data['msg'] = "Success";
			} else {
				$data['msg'] = "No Record Found";
			}
		}else{
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function getDriverDetailByOwner(){
		if($this->input->post('iVehicleOwnerId') && $this->input->post('iDriverId')){
			$driverInfo = $this->webservices_model->getDriverInfo($this->input->post('iVehicleOwnerId'),$this->input->post('iDriverId'));
			if ($driverInfo){
				if ($driverInfo['vProfileImage']=='') {
					$driverInfo['driver_image_url'] = $this->data['base_upload'].'plash-holder.png';
				} else {
					$driver_image_path = $this->data['base_path'].'uploads/driver/'.$driverInfo['iDriverId'].'/'.$driverInfo['vProfileImage'];
					if (file_exists($driver_image_path)){
						$driverInfo['driver_image_url'] = $this->data['base_upload'].'driver/'.$driverInfo['iDriverId'].'/'.$driverInfo['vProfileImage'];
					}else{
						$driverInfo['driver_image_url'] = $this->data['base_upload'].'plash-holder.png';
					}
				}
				$driverInfo['eAvailability']=($driverInfo['eAvailability']=='Both')?'Both (Local / Outstation)':$driverInfo['eAvailability'];
				if (strpos($driverInfo['iMobileNo1'], '-') !== false) {
					$iMobileNo=explode('-',$driverInfo['iMobileNo1']);
					$driverInfo['vCountryMobileCode']=$iMobileNo[0];
					$driverInfo['iMobileNo1']=$iMobileNo[1];
				}
				$data['data'] = $driverInfo;
				$data['msg'] = "Success";
			} else {
				$data['msg'] = "No Record Found";
			}
		}else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function outOfStationList(){
		if($this->input->post('iVehicleOwnerId') && $this->input->post('type')){
			$iVehicleOwnerId = $this->input->post('iVehicleOwnerId');
			$type = $this->input->post('type');
			$owner_exists = $this->webservices_model->check_owner_exists($iVehicleOwnerId);
			if ($owner_exists=='exist') {
				if($type =='Active' || $type =='Expired'){
					$driversArr = $this->webservices_model->allDriversByOwnerID($iVehicleOwnerId);
					$resArr=array();
					foreach ($driversArr as $dkey => $driver) {
						$outStation = $this->webservices_model->get_outstationlistby_owner($driver['iDriverId'],$type); 
						$resArr = array_merge($resArr,$outStation);
					}
					$data['data'] = $resArr;
					$data['msg'] = 'Success';
				}else{
					$data['msg'] = 'Invalid Type';
				}
			} else {
				$data['msg'] = "Vehical Owner Not Exist";
			}
		}else {
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function AddOutOfStationByOwner(){
		if($this->input->post('iDriverId') && $this->input->post('vFromCity') && $this->input->post('vToCity') && $this->input->post('dFromDate') && $this->input->post('dToDate') && $this->input->post('iAdditionalDiscount')){
			// check max 10
			$iDriverId = $this->input->post('iDriverId');
			$allActive = $this->webservices_model->getActiveOutOfStationByDriver($iDriverId);
			if (count($allActive) < 10) {
				// check Date
				$dFromDate = $this->input->post('dFromDate');
				$dToDate = $this->input->post('dToDate');
				$strFr = strtotime($dFromDate);
				$strTo = strtotime($dToDate);
				$now = strtotime(date('Y-m-d H:i:s'));
				if ($strTo > $strFr && $strTo > $now && $strFr > $now) {
					$resFr = $this->webservices_model->checkDatesforOutstation($iDriverId,'dFromDate',$dFromDate,$dToDate);
					$resTo = $this->webservices_model->checkDatesforOutstation($iDriverId,'dToDate',$dFromDate,$dToDate);
					if ($resFr == 0 && $resTo == 0) {
						$outStAvb['iDriverId'] = $iDriverId;
						$outStAvb['vFromAddress'] = $this->input->post('vFromCity');
						$outStAvb['vToAddress'] = $this->input->post('vToCity');
						$outStAvb['dFromDate'] = $dFromDate;
						$outStAvb['dToDate'] = $dToDate;
						$outStAvb['vFromCity'] = $this->GetCityFromAddressOrLatLong($this->input->post('vFromCity'),'address');
						$outStAvb['vToCity'] = $this->GetCityFromAddressOrLatLong($this->input->post('vToCity'),'address');
						$outStAvb['iAdditionalDiscount'] = $this->input->post('iAdditionalDiscount');
						// Save data
						$iOutOfStationId = $this->webservices_model->add_outstation_availability($outStAvb);
						if ($iOutOfStationId > 0) {
							$Data['msg'] = "Success";
						}else{
							$Data['msg'] = 'Failure';
						}
					} else {
						$Data['msg'] = 'Outstation Availability for selected Driver is Already Exist Between Selected Dates';
					}
				} else {
					$Data['msg'] = 'Invalid Dates';
				}
			} else {
				$Data['msg'] = 'Maximum Limit is 10 for Outstation Availability';
			}
		}else{
			$Data['msg'] = 'Error';
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function deleteDriverByOwner(){
		if ($this->input->post('iDriverId')) {
			$iDriverId=$this->input->post('iDriverId');
			// Remove Driver related data
			// Remove Files from folder by iDriverId : driver, police_report, regular_license
			$url = $this->data['base_path'] . 'uploads/regular_license/'. $iDriverId.'/';
			array_map('unlink',glob($url."/*"));
			rmdir($this->data['base_path'].'uploads/regular_license/'.$iDriverId);

			$url1 = $this->data['base_path'] . 'uploads/police_report/'. $iDriverId.'/';
			array_map('unlink',glob($url1."/*"));
			rmdir($this->data['base_path'].'uploads/police_report/'.$iDriverId);

			$url4 = $this->data['base_path'] . 'uploads/driver/'. $iDriverId.'/';
			array_map('unlink',glob($url4."/*"));
			rmdir($this->data['base_path'].'uploads/driver/'.$iDriverId);
			// delete licence
			$this->webservices_model->delete_driver_record("iDriverId", $iDriverId, 'drivers_licence');
			// delete outstan
			$this->webservices_model->delete_driver_record("iDriverId", $iDriverId, 'out_of_station');
			// delete transactions
			$delete_transac = $this->webservices_model->delete_driver_record("iDriverId", $iDriverId, 'transactions');
			
			// Update : vehicle_attribute
			$this->webservices_model->remove_driver_vehicle($iDriverId);
			
			$trips = $this->webservices_model->get_driver_tripids($iDriverId);
			
			foreach ($trips as $tkey => $trip) {
				$delete_trpartner = $this->webservices_model->delete_driver_record("iTripId", $trip['iTripId'], 'trip_partners');
				$delete_trpromocode = $this->webservices_model->delete_driver_record("iTripId", $trip['iTripId'], 'trip_invitation_promocode_usage');
				$delete_trip = $this->webservices_model->delete_driver_record("iTripId", $trip['iTripId'], 'trip_detail');
			}
			$delete_trdrivr = $this->webservices_model->delete_driver_record("iDriverId", $iDriverId, 'trip_drivers');
			$device = $this->webservices_model->get_driver_device_details($iDriverId);
			
			$devicecnt = $this->webservices_model->delete_existing_devices($iDriverId,'Driver');
			$devicesRes = $this->webservices_model->delete_device(2,$device['device_id']);
			$delete_driver = $this->webservices_model->delete_driver_record("iDriverId", $iDriverId, 'driver');

			$data['msg'] = "Success";
		} else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}
	// ********************* Functions for Vehical Owner end *********************

	// ****************************** New Functions ******************************
	// For make Out of service
	function UnSetOutOfService(){
		if($this->input->post('type')=='driver' || $this->input->post('type')=='owner'){
			if($this->input->post('type')=='owner'){
				if($this->input->post('iVehicleOwnerId')){
					// Make VO OutOfService
					$this->webservices_model->update_owner_detail(array('eOutOfService'=>'No'), $this->input->post('iVehicleOwnerId'));
					// Make VO Drivers OutOfService
					$this->webservices_model->update_driver_detail_byowner(array('eOutOfService'=>'No'), $this->input->post('iVehicleOwnerId'));
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "Failure";
				}
			}
			if($this->input->post('type')=='driver'){
				if ($this->input->post('iDriverId')) {
					$this->webservices_model->update_driver_detail(array('eOutOfService'=>'No'), $this->input->post('iDriverId'));
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "Failure";
				}
			}
		} else {
			$data['msg'] = "Failure";
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function getOutOfServiceStatus(){
		if($this->input->post('type')=='driver' || $this->input->post('type')=='owner'){
			if($this->input->post('type')=='owner'){
				if($this->input->post('iVehicleOwnerId')){
					$currentStatus=$this->webservices_model->getservicestatus('vehicle_owner','iVehicleOwnerId',$this->input->post('iVehicleOwnerId'));
					$data['data'] = $currentStatus;
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "Failure";
				}
			}
			if($this->input->post('type')=='driver'){
				if($this->input->post('iDriverId')){
					$currentStatus = $this->webservices_model->getservicestatus('driver','iDriverId',$this->input->post('iDriverId'));
					$data['data'] = $currentStatus;
					$data['msg'] = "Success";
				}else{
					$data['msg'] = "Failure";
				}
			}
		}else{
			$data['msg'] = "Failure";
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function SetOutOfService(){
		if($this->input->post('type')=='driver' || $this->input->post('type')=='owner'){
			if(($this->input->post('type')=='owner') && ($this->input->post('set')=='Yes'||$this->input->post('set')=='No')){
				if($this->input->post('iVehicleOwnerId')){
					$newArr = array('eOutOfService'=>$this->input->post('set'));
					// Make VO OutOfService
					$this->webservices_model->update_owner_detail($newArr, $this->input->post('iVehicleOwnerId'));
					// Make VO Drivers OutOfService
					$this->webservices_model->update_driver_detail_byowner($newArr, $this->input->post('iVehicleOwnerId'));
					$currentStatus = $this->webservices_model->getservicestatus('vehicle_owner','iVehicleOwnerId',$this->input->post('iVehicleOwnerId'));
					$data['data'] = $currentStatus;
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "Failure";
				}
			}
			if(($this->input->post('type')=='driver') && ($this->input->post('set')=='Yes'||$this->input->post('set')=='No')){
				if ($this->input->post('iDriverId')) {
					if ($this->input->post('set')=='No') {
						// Check Owner status
						$voStatus = $this->webservices_model->getOwnerServicestatusByDriver($this->input->post('iDriverId'));
						if ($voStatus['eOutOfService']=='Yes') {
							$currentStatus = $this->webservices_model->getservicestatus('driver','iDriverId',$this->input->post('iDriverId'));
							$data['data'] = $currentStatus;
							$data['msg'] = "Vehicle Owner is Out Of Service";
							$main = json_encode($data);
							echo $main;
							exit;
						}
					}
					$newArr = array('eOutOfService'=>$this->input->post('set'));
					$this->webservices_model->update_driver_detail($newArr, $this->input->post('iDriverId'));
					$currentStatus = $this->webservices_model->getservicestatus('driver','iDriverId',$this->input->post('iDriverId'));
					$data['data'] = $currentStatus;
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "Failure";
				}
			}
		} else {
			$data['msg'] = "Failure";
		}
		$main = json_encode($data);
		echo $main;exit;
	}

	function GetAllCarsByCompany(){
		if($_REQUEST['latitude'] && $_REQUEST['longitude'] && $_REQUEST['circleType']){
			$cur_lat = $_REQUEST['latitude'];
			$cur_long = $_REQUEST['longitude'];
			$circleType = $_REQUEST['circleType']; // Taxi / CarPool / Local / OutStation / Shuttle / FixedRide
			if ($circleType=='FixedRide') {
				if ($_REQUEST['ride_id']) {
					$carid = ($_REQUEST['car_id']) ? $_REQUEST['car_id'] : '' ;
					$this->getAllFixedCars($_REQUEST['ride_id'],$carid);
				} else {
					$data['msg'] = "Failure";
					echo json_encode($data);exit;
				}
			}
			$source_address = $_REQUEST['source_address'];
			if($source_address){
				$source_lat_long = $this->GetLatLongFromAddress($source_address);
				$source_lat_long_arr = explode('|', $source_lat_long);
				$dLatitude=$source_lat_long_arr[0];
				$dLongitude=$source_lat_long_arr[1];
			}else{
				$dLatitude=$cur_lat;
				$dLongitude=$cur_long;
			}
			$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKM($dLatitude,$dLongitude);
			$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMile($dLatitude,$dLongitude);
			
			// check within radius
			$check_km=($source_city_status_km['distance'] <= $source_city_status_km['fRadius']) ? $source_city_status_km : 0;
			$check_mile = ($source_city_status_mile['distance'] <= $source_city_status_mile['fRadius']) ? $source_city_status_mile : 0;
			if($check_km != 0 || $check_mile != 0){
				if($check_km != 0 && $check_mile == 0){
					$source_citydetail = $check_km;
				}else if($check_mile != 0 && $check_km == 0){
					$source_citydetail = $check_mile;
				}else if($check_mile != 0 && $check_km != 0){
					$distanceFromKM = $check_km['distance'];
					$distanceFromMile=$check_mile['distance']*1.609344;
					$source_citydetail = ($distanceFromKM < $distanceFromMile) ? $check_km : $check_mile ;
				}else{
					$data['msg'] = "Service Not Available In Your Pick UP Address City";
					echo json_encode($data);exit;
				}
				$distanceUnit = $source_citydetail['eRadiusUnit'];
				$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
				$source_city_lat_long = explode('|', $source_citydetail['tCityLatLong']);
				$source_city_radius =$source_citydetail['fRadius'];
				$carid = ($_REQUEST['car_id']) ? $_REQUEST['car_id'] : '' ;
				if($circleType=='Taxi'){
					$destination_address = mysql_real_escape_string($_REQUEST['destination_address']);
					if ($destination_address!="") {
						$finishlatlong = $this->GetLatLongFromAddress($destination_address);
						// Check Local or OutStation
						$check_distance = $this->GetMileKMFromLatLong($source_citydetail['tCityLatLong'],$finishlatlong);
						if ($distanceUnit=='Miles') {
							$ridelocation = ($check_distance['miles']<=$source_city_radius) ? "local" : "outstation" ;
						} else {
							$ridelocation = ($check_distance['kms']<=$source_city_radius) ? "local" : "outstation" ;
						}
						if ($ridelocation=='local') {
							$ridelocationArr[] = 'Local';
							$ridelocationArr[] = 'Both';
						} else {
							$ridelocationArr[] = 'Outstation';
							$ridelocationArr[] = 'Both';
						}
					} else {
						$ridelocationArr[] = 'Local';
						$ridelocationArr[] = 'Outstation';
						$ridelocationArr[] = 'Both';
					}
				}else if($circleType=='CarPool'){
					// prepare request pera
					$searchPoolCars['eSmokingPreference']=$_REQUEST['eSmokingPreference'];
					$searchPoolCars['eGenderPreference']=$_REQUEST['eGenderPreference'];
					$searchPoolCars['source_city']=$source_citydetail;
					$searchPoolCars['carid']=$carid;
					$searchPoolCars['qurVar']=$qurVar;
					$searchPoolCars['sLat']=$dLatitude;
					$searchPoolCars['sLong']=$dLongitude;
					$searchPoolCars['source_address']=$_REQUEST['source_address'];
					$destination_address = mysql_real_escape_string($_REQUEST['destination_address']);
					$searchPoolCars['destination_address']=$destination_address;
					if ($destination_address!="") {
						$finishlatlong = $this->GetLatLongFromAddress($destination_address);
						$flatlong = explode('|', $finishlatlong);
						$searchPoolCars['fLat']=$flatlong[0];
						$searchPoolCars['fLong']=$flatlong[1];
						// Check Local or OutStation
						$check_distance = $this->GetMileKMFromLatLong($source_citydetail['tCityLatLong'],$finishlatlong);
						if ($distanceUnit=='Miles') {
							$ridelocation = ($check_distance['miles']<=$source_city_radius) ? "local" : "outstation" ;
						} else {
							$ridelocation = ($check_distance['kms']<=$source_city_radius) ? "local" : "outstation" ;
						}
						if ($ridelocation=='local') {
							$ridelocationArr[] = 'CarPoolLocal';
							$ridelocationArr[] = 'CarPoolBoth';
						} else {
							$ridelocationArr[] = 'CarPoolOutstation';
							$ridelocationArr[] = 'CarPoolBoth';
						}
					} else {
						$ridelocationArr[] = 'CarPoolLocal';
						$ridelocationArr[] = 'CarPoolOutstation';
						$ridelocationArr[] = 'CarPoolBoth';
						$searchPoolCars['fLat']='';
						$searchPoolCars['fLong']='';
					}
					$searchPoolCars['ridelocationArr']=$ridelocationArr;
					// Call Helper function
					$this->getAllCarpoolCars($searchPoolCars);
				}else if($circleType=='LocalByDuration'){
					$ridelocationArr[] = 'LocalByDuration';
					$ridelocationArr[] = 'BothByDuration';
				}else if($circleType=='OutStationByDuration'){
					$ridelocationArr[] = 'OutStationByDuration';
					$ridelocationArr[] = 'BothByDuration';
				}else if($circleType=='Shuttle'){
					$ridelocationArr[] = 'Shuttle';
				}else{
					$data['msg'] = 'Failure';
					echo json_encode($data);exit;
				}

				// check drivers available in source city
				$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0], $source_city_lat_long[1], $source_city_radius, $qurVar,$ridelocationArr,$carid);
				if ($drivers) {
					$availableDriver=array();
					foreach ($drivers as $dkey => $dvalue) {
						$countRunningTrip = $this->webservices_model->checkRunningTripByDriver($dvalue['iDriverId']);
						if($countRunningTrip==0){
							$availableDriver[] = $dvalue;
						}
					}
					if(count($availableDriver) > 0){
						$data['data']= $availableDriver;
						$data['msg'] = "Success";
					}else{
						$data['msg'] = "No Car Found in Pick UP Location";
					}
				} else {
					$data['msg'] = "No Car Found in Pick UP Location";
				}
			}else{
				$data['msg'] = "Service Not Available In Your Pick UP Address City";
				echo json_encode($data);exit;
			}
		}else {
			$data['msg'] = 'Failure';
		}
		$main = json_encode($data);
		echo $main;exit;
	}

	//helper function for GetAllCarsByCompany as : carpool
	function getAllCarpoolCars($givenData){
		// $this->printthis("givenData");
		// $this->printthis($givenData);
		if ($givenData['eSmokingPreference']==''||$givenData['eGenderPreference']=='') {
			$data['msg'] = 'Failure';
			echo json_encode($data);exit;
		}
		$citydetail = $this->webservices_model->get_one_city_details_byid($givenData['source_city']['iCityId']);
		// set Pref arr
		if($givenData['eSmokingPreference']=='Yes'){
			$smokingPref= array('Yes','Does Not Matter');
		}else if($givenData['eSmokingPreference']=='No'){
			$smokingPref= array('No');
		}else{
			$smokingPref= array('Yes','No','Does Not Matter');
		}

		if($givenData['eGenderPreference']=='Male'){
			$genderPref= array('Male','Does Not Matter');
		}else if($givenData['eGenderPreference']=='Female'){
			$genderPref= array('Female','Does Not Matter');
		}else{
			$genderPref= array('Male','Female','Does Not Matter');
		}
		// $this->printthis("smokingPref");
		// $this->printthis($smokingPref);
		// $this->printthis("genderPref");
		// $this->printthis($genderPref);
		// $this->printthisexit("END");
		
		if ($givenData['fLat'] =='' && $givenData['fLong'] =='') {
			# check with sLat, sLong with source address
			$drivers = $this->webservices_model->getAllPoolDriversWithinCity($givenData['sLat'],$givenData['sLong'],$citydetail['fSourceRadius'], $givenData['qurVar'],$givenData['ridelocationArr'],$givenData['carid'],$smokingPref,$genderPref);
			// $this->printthis("drivers");
			// $this->printthis($drivers);
			// get unique driver ids
			$driverTmpArr= array();
			$driverArr= array();
			foreach ($drivers as $dskey => $drSource){
				if (!in_array($drSource['iDriverId'], $driverArr)) {
					$countRunningTrip = $this->webservices_model->checkRunningTripByDriver($drSource['iDriverId']);
					if($countRunningTrip==0){
						$driverArr[]=$drSource['iDriverId'];
						$tmp= array();
						$tmp['iDriverId']=$drSource['iDriverId'];
						$tmp['distance']=$drSource['distance'];
						$driverTmpArr[]=$tmp;
					}
				}
			}
		}else {
		
			# check with sLat, sLong with source address, and dest too
			$driversbySource = $this->webservices_model->getAllPoolDriversWithinCity($givenData['sLat'],$givenData['sLong'],$citydetail['fSourceRadius'], $givenData['qurVar'],$givenData['ridelocationArr'],$givenData['carid'],$smokingPref,$genderPref);
			// $this->printthis("driversbySource");
			// $this->printthis($driversbySource);
			$iCarpoolIds= array();
			foreach ($driversbySource as $dskey => $drSource){
				$iCarpoolIds[]=$drSource['iCarpoolId'];
			}
			if (count($iCarpoolIds)==0) {
				$data['msg'] = "No Car Found in Pick UP Location";
				$main = json_encode($data);
				echo $main;exit;
			}
			// $this->printthis("iCarpoolIds");
			// $this->printthis($iCarpoolIds);
			// exit;
			// get destination city radius
			$destCity = $this->getCityFromLatLongAddress("LatLong",$givenData['fLat'].'|'.$givenData['fLong']);
			/*$this->printthis("destCity");
			$this->printthis($destCity);*/
			$destCitydetail = $this->webservices_model->get_one_city_details_byid($destCity['iCityId']);
			/*$this->printthis("destCitydetail");
			$this->printthisexit($destCitydetail);*/

			$qurVardest = ($destCitydetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
			$driversbyDest = $this->webservices_model->getAllPoolDriversBySource($givenData['fLat'],$givenData['fLong'],$destCitydetail['fDestinationRadius'], $qurVardest,$givenData['ridelocationArr'],$givenData['carid'],$smokingPref,$genderPref,$iCarpoolIds);
			// $this->printthis("driversbyDest");
			// $this->printthis($driversbyDest);
			// get unique driver ids

			$driverArr= array();
			$driverTmpArr= array();
			if (count($driversbyDest) > 0) {
				foreach ($driversbyDest as $dskey => $drDest){
					if (!in_array($drDest['iDriverId'], $driverArr)) {
						$countRunningTrip = $this->webservices_model->checkRunningTripByDriver($drDest['iDriverId']);
						if($countRunningTrip==0){
							$driverArr[]=$drDest['iDriverId'];
							$tmp= array();
							$tmp['iDriverId']=$drDest['iDriverId'];
							$tmp['distance']=$drDest['distance'];
							$driverTmpArr[]=$tmp;
						}
					}
				}
			}
		}
		$availableDriver=array();
		foreach ($driverTmpArr as $dkey => $dvalue) {
			$driverVehicleInfo = $this->webservices_model->getPoolDriverVehicle($dvalue['iDriverId']);
			if ($driverVehicleInfo) {
				$driverVehicleInfo['distance']=$dvalue['distance'];
				$availableDriver[] = $driverVehicleInfo;
			}
		}
		if(count($availableDriver) > 0){
			$data['data']= $availableDriver;
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "No Car Found in Pick UP Location";
		}
		$main = json_encode($data);
		echo $main;exit;
	}

	// With Time Zone
	function GetFareEstimation(){
		// Taxi / CarPool / Local / OutStation / Shuttle
		$givenData = $this->input->post();
		// $this->printthisexit($givenData);
		if($givenData['circleType'] =='FixedRide'){
			if($givenData['ride_id'] != 0 && $givenData['car_id'] != 0 && $givenData['booktype']){
				$this->estimateFixedRide($givenData);
			}else{
				$data['msg'] = "Failure";
				echo json_encode($data);exit;
			}
		}
		
		if($givenData['circleType'] && $givenData['source_address']){
			//------------------------------------------------------------------------------------------------
			$source_lat_long = $this->GetLatLongFromAddress($givenData['source_address']);
			$source_lat_long_arr = explode('|', $source_lat_long);
			$dLatitude=$source_lat_long_arr[0];
			$dLongitude=$source_lat_long_arr[1];
			$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKM($dLatitude,$dLongitude);
			$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMile($dLatitude,$dLongitude);
			// check within radius
			$check_km=($source_city_status_km['distance'] <= $source_city_status_km['fRadius']) ? $source_city_status_km : 0;
			$check_mile = ($source_city_status_mile['distance'] <= $source_city_status_mile['fRadius']) ? $source_city_status_mile : 0;
			if($check_km != 0 || $check_mile != 0){
				if($check_km != 0 && $check_mile == 0){
					$sourceCityDetail = $check_km;
				}else if($check_mile != 0 && $check_km == 0){
					$sourceCityDetail = $check_mile;
				}else if($check_mile != 0 && $check_km != 0){
					$distanceFromKM = $check_km['distance'];
					$distanceFromMile=$check_mile['distance']*1.609344;
					$sourceCityDetail = ($distanceFromKM < $distanceFromMile) ? $check_km : $check_mile ;
				}else{
					$data['msg'] = "Service Not Available In Your Pick UP Address City";
					echo json_encode($data);exit;
				}
			}else{
				$data['msg'] = "Service Not Available In Your Pick UP Address City";
				echo json_encode($data);exit;
			}
			//------------------------------------------------------------------------------------------------
			$source_address_city_status = $this->webservices_model->check_city_exist($sourceCityDetail['vCity']);
			// check source city is available for service
			if($source_address_city_status=='exist'){
				$source_citydetail = $this->webservices_model->get_one_city_details($sourceCityDetail['vCity']);
				if($givenData['circleType']=='Taxi'){
					if ($givenData['source_address'] && $givenData['destination_address'] && $givenData['booktype'] && $givenData['car_id']){
						// Call helper function
						// $data['msg'] = "Tmp Success";
						$this->estimateTaxi($givenData,$source_citydetail,$source_lat_long);
					} else {
						$data['msg'] = "Failure";
					}
				}else if($this->input->post('circleType')=='CarPool'){
					if ($givenData['source_address'] && $givenData['destination_address'] && $givenData['booktype'] && $givenData['car_id']){
						// Call helper
						$this->estimateCarPool($givenData,$source_citydetail,$source_lat_long);
					}else{
						$data['msg'] = "Failure";
					}
				}else if($this->input->post('circleType')=='LocalByDuration'){
					if ($givenData['vRoundOption'] && $givenData['booktype'] && $givenData['car_id']) {
						// Call helper function
						$this->estimateLocalRound($givenData,$source_citydetail,$source_lat_long);
					} else {
						$data['msg'] = "Failure";
					}
				}else if($this->input->post('circleType')=='OutStationByDuration'){
					if ($givenData['destination_address'] && $givenData['vRoundOption'] && $givenData['booktype'] && $givenData['car_id']) {
						// Call helper function
						$this->estimateOutRound($givenData,$source_citydetail,$source_lat_long);
					} else {
						$data['msg'] = "Failure";
					}
				}else if($this->input->post('circleType')=='Shuttle'){
					if ($givenData['destination_address'] && $givenData['total_adults'] && $givenData['booktype'] && $givenData['car_id']) {
						// Call helper function
						$this->estimateShuttle($givenData,$source_citydetail,$source_lat_long);
					} else {
						$data['msg'] = "Failure";
					}
				}else{
					$data['msg'] = "Failure";
				}
			}else{
				$data['msg'] = "Service Not Available In Your Pick UP Address City";
			}
			//------------------------------------------------------------------------------------------------
		}else {
			$data['msg'] = "Failure";
		}
		// Send Response
		$response = json_encode($data);
		echo $response;exit;
	}

	// Helper for Farequote option : Taxi
	function estimateTaxi($givenData,$source_citydetail,$startlatlong){
		// lat longs
		$destination_address = mysql_real_escape_string($givenData['destination_address']);
		$finishlatlong = $this->GetLatLongFromAddress($destination_address);
		$distanceUnit = $source_citydetail['eRadiusUnit'];
		$source_city_radius =$source_citydetail['fRadius'];

		// Check Local or OutStation
		$check_distance = $this->GetMileKMFromLatLong($source_citydetail['tCityLatLong'],$finishlatlong);
		if ($distanceUnit=='Miles') {
			$ridelocation = ($check_distance['miles']<=$source_city_radius) ? "local" : "outstation" ;
		} else {
			$ridelocation = ($check_distance['kms']<=$source_city_radius) ? "local" : "outstation" ;
		}

		// get distance
		$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);
		if($finalkmminarr=='none'){
			$data['msg'] = "Failure";
			// Send Response
			$response = json_encode($data);
			echo $response;exit;
		}
		
		$currencySymbol = $source_citydetail['vCurrencySymbol'];
		$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
		$source_city_lat_long = explode('|', $source_citydetail['tCityLatLong']);
		
		if ($ridelocation=='local') {
			$ridelocationArr[] = 'Local';
			$ridelocationArr[] = 'Both';
		} else {
			$ridelocationArr[] = 'Outstation';
			$ridelocationArr[] = 'Both';
		}

		// check drivers available in source city
		$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$source_city_radius,$qurVar,$ridelocationArr,$givenData['car_id']);
		$availableDriverCount = 0;
		foreach ($drivers as $drkey => $driver) {
			$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($driver['iDriverId']);
			if($countRunningTrip==0){
				$availableDriverCount++;
			}
		}
		
		$fare_quote = $this->webservices_model->get_fare_quote_details_new($source_citydetail['iCityId'],$givenData['car_id'],$ridelocation,'One Way');
		if(!empty($fare_quote)){
			$mainarr['taxi_name'] = $fare_quote['vCompany'];
			if ($distanceUnit=='KMs') {
				$distance = $finalkmminarr['distance_in_km'];
				$mainarr['distance'] = $distance.' KMS'; 
			} else {
				$distance = $finalkmminarr['distance_in_mile'];
				$mainarr['distance'] = $distance.' MILES'; 
			}
			$mainarr['duration'] = strtoupper($finalkmminarr['original_duration']);

			if($ridelocation=='local') {
				// Consider : BaseFare, MinimumFare, MinimumMiles, perMileFare, PerMinuteFare
				$mainarr['ridelocation'] = 'Local';
				if ($givenData['booktype']=='now') {
					$tmpDate = new DateTime("now", new DateTimeZone($source_citydetail['vTimeZone']));
					$book_time = $tmpDate->format('Y-m-d H:i:s');
				} else {
					$tmpDate = new DateTime($givenData['book_time'], new DateTimeZone($source_citydetail['vTimeZone']));
					$book_time = $tmpDate->format('Y-m-d H:i:s');
				}
				
				if($distance < $fare_quote['fMinimumKm']){
					$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
					$mainarr['total_fare_without_currency'] = number_format($fare_quote['fThreeKmFare'], 2);
				}else {
					$perMileFare = 0;
					$finalfare = $fare_quote['fBaseFare'];
					$current_hr_min=date('H:i', strtotime($book_time));
					if ($source_citydetail['ePicksurchargestatus']=='Yes') {
						$vPicktimefrom = date('H:i', strtotime($source_citydetail['vPicktimefrom']));
						$vPicktimeto = date('H:i', strtotime($source_citydetail['vPicktimeto']));
						if ($this->timeisBetween($vPicktimefrom,$vPicktimeto,$current_hr_min))
						{
							$perMileFare = $fare_quote['fPerMileFare']+(($fare_quote['fPerMileFare']*$fare_quote['fPeaktimesurcharge'])/100);
						}
					}
					if ($source_citydetail['eNightsurchargestatus']=='Yes') {
						$vNighttimefrom = date('H:i', strtotime($source_citydetail['vNighttimefrom']));
						$vNighttimeto = date('H:i', strtotime($source_citydetail['vNighttimeto']));
						if ($this->timeisBetween($vNighttimefrom,$vNighttimeto,$current_hr_min))
						{
							$perMileFare = $fare_quote['fPerMileFare'] + ($fare_quote['fPerMileFare'] * ($fare_quote['fNighttimesurcharge']) / 100);
						}
					}
					$perMileFare = ($perMileFare==0) ? $fare_quote['fPerMileFare'] : $perMileFare ;
					
					$totalfareinmiles = ($perMileFare * $distance);
					$totalfareinmins = ($fare_quote['fPerMinFare'] * $finalkmminarr['duration_in_mins']);
					$finalfare = $finalfare+($totalfareinmiles+$totalfareinmins);
					$finalfare = $finalfare + (($finalfare * $source_citydetail['fServicetax'])/100);
					if (number_format($fare_quote['fThreeKmFare'], 2) > $finalfare) {
						$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
						$mainarr['total_fare_without_currency'] = number_format($fare_quote['fThreeKmFare'], 2);
					} else {
						$mainarr['total_fare'] = $currencySymbol.(number_format($finalfare,2));
						$mainarr['total_fare_without_currency'] =number_format($finalfare,2);
					}
				}
				
			}
			if($ridelocation=='outstation') {
				// Consider : MinimumFare, MinimumMiles, perMileFare
				$mainarr['ridelocation'] = 'Outstation';
				if($distance < $fare_quote['fMinimumKm']){
					$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
					$mainarr['total_fare_without_currency'] =number_format($fare_quote['fThreeKmFare'], 2);
				}else {
					$finalfare = ($fare_quote['fPerMileFare'] * $distance);
					$finalfare = $finalfare + (($finalfare * $source_citydetail['fServicetax'])/100);
					if (number_format($fare_quote['fThreeKmFare'], 2) > $finalfare) {
						$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
						$mainarr['total_fare_without_currency'] =number_format($fare_quote['fThreeKmFare'], 2);
					} else {
						$mainarr['total_fare'] = $currencySymbol.(number_format($finalfare,2));
						$mainarr['total_fare_without_currency'] =number_format($finalfare,2);
					}
				}
			}
			$mainarr['seating_capacity'] = $fare_quote['vSeatingCapacity'];
			$mainarr['source_address'] = $givenData['source_address'];
			$mainarr['destination_address'] = $givenData['destination_address'];
			$mainarr['fare_currency'] = $currencySymbol;
			$mainarr['iCityId'] = $source_citydetail['iCityId'];
			$data['data'] = $mainarr;
			if ($givenData['booktype']=='now' && $availableDriverCount == 0) {
				$data['msg'] = 'No Car Found in Pick UP Location';
			} else {
				$data['msg'] = 'Success';
			}

		}else{
			$data['msg'] = "No Fare Available";
		}
		// Send Response
		$response = json_encode($data);
		echo $response;exit;
	}

	// Helper for Farequote option : CarPool
	function estimateCarPool($givenData,$source_citydetail,$startlatlong){
		// - Source, Destination, BOOK NOW / BOOK LATER, car_id
		// lat longs
		$destination_address = mysql_real_escape_string($givenData['destination_address']);
		$finishlatlong = $this->GetLatLongFromAddress($destination_address);
		$distanceUnit = $source_citydetail['eRadiusUnit'];
		$source_city_radius =$source_citydetail['fRadius'];

		// Check Local or OutStation
		$check_distance = $this->GetMileKMFromLatLong($source_citydetail['tCityLatLong'],$finishlatlong);
		if ($distanceUnit=='Miles') {
			$ridelocation = ($check_distance['miles']<=$source_city_radius) ? "CarPoolLocal" : "CarPoolOutstation" ;
		} else {
			$ridelocation = ($check_distance['kms']<=$source_city_radius) ? "CarPoolLocal" : "CarPoolOutstation" ;
		}

		// get distance
		$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);
		if($finalkmminarr=='none'){
			$data['msg'] = "Failure";
			// Send Response
			$response = json_encode($data);
			echo $response;exit;
		}
		
		$currencySymbol = $source_citydetail['vCurrencySymbol'];
		$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
		$source_city_lat_long = explode('|', $source_citydetail['tCityLatLong']);
		
		if ($ridelocation=='CarPoolLocal') {
			$ridelocationArr[] = 'CarPoolLocal';
			$ridelocationArr[] = 'CarPoolBoth';
		} else {
			$ridelocationArr[] = 'CarPoolOutstation';
			$ridelocationArr[] = 'CarPoolBoth';
		}

		// check drivers available in source city
		// Get all carpools of source city
		// Check with date source radius, dest redius are come to filter
		$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$source_city_radius,$qurVar,$ridelocationArr,$givenData['car_id']);

		$availableDriverCount = 0;
		foreach ($drivers as $drkey => $driver) {
			$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($driver['iDriverId']);
			if($countRunningTrip==0){
				$availableDriverCount++;
			}
		}
		
		$fare_quote = $this->webservices_model->get_fare_quote_details_new($source_citydetail['iCityId'],$givenData['car_id'],$ridelocation,'One Way');
		if(!empty($fare_quote)){
			$mainarr['taxi_name'] = $fare_quote['vCompany'];
			if ($distanceUnit=='KMs') {
				$distance = $finalkmminarr['distance_in_km'];
				$mainarr['distance'] = $distance.' KMS'; 
			} else {
				$distance = $finalkmminarr['distance_in_mile'];
				$mainarr['distance'] = $distance.' MILES'; 
			}
			$mainarr['duration'] = strtoupper($finalkmminarr['original_duration']);
			// Consider : MinimumFare, MinimumMiles, perMileFare
			$mainarr['ridelocation'] = $ridelocation;
			
			$finalfare = ($fare_quote['fPerMileFare'] * $distance);
				
			if (number_format($fare_quote['fThreeKmFare'], 2) > $finalfare) {
				$mainarr['total_fare'] = $currencySymbol.(number_format($fare_quote['fThreeKmFare'], 2));
				$mainarr['total_fare_without_currency'] =number_format($fare_quote['fThreeKmFare'], 2);
			} else {
				$mainarr['total_fare'] = $currencySymbol.(number_format($finalfare,2));
				$mainarr['total_fare_without_currency'] =number_format($finalfare,2);
			}
			
			$mainarr['seating_capacity'] = $fare_quote['vSeatingCapacity'];
			$mainarr['source_address'] = $givenData['source_address'];
			$mainarr['destination_address'] = $givenData['destination_address'];
			$mainarr['fare_currency'] = $currencySymbol;
			$mainarr['iCityId'] = $source_citydetail['iCityId'];
			//$mainarr['drivers'] = $drivers;  // Remove after test****************************************
			$data['data'] = $mainarr;
			if ($givenData['booktype']=='now' && $availableDriverCount == 0) {
				$data['msg'] = 'No Car Found in Pick UP Location';
			} else {
				$data['msg'] = 'Success';
			}
		}else{
			$data['msg'] = "No Fare Available";
		}
		// Send Response
		header('Content-type: application/json');
		$response = json_encode($data);
		echo $response;exit;
	}
	
	// Helper for Farequote option : Local By Duration
	function estimateLocalRound($givenData,$source_citydetail,$source_lat_long){
		/*$this->printthis($givenData);
		$this->printthis($source_citydetail);
		$this->printthisexit($source_lat_long);*/
		//---------------------------------------------------
		$source_city_lat_long = explode('|', $source_citydetail['tCityLatLong']);
		$qurVar = ($source_citydetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
		$ridelocationArr = array('LocalByDuration','BothByDuration');
		// check drivers available in source city
		$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$source_citydetail['fRadius'],$qurVar,$ridelocationArr,$givenData['car_id']);
		$availableDriverCount = 0;
		foreach ($drivers as $drkey => $driver) {
			$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($driver['iDriverId']);
			if($countRunningTrip==0){
				$availableDriverCount++;
			}
		}
		// Consider : fOption1, fOption2, fOption3, fOption4
		$roundOption = $givenData['vRoundOption'];
		$fare_quote = $this->webservices_model->get_fare_quote_details_new($source_citydetail['iCityId'],$givenData['car_id'],'LocalByDuration','Round');
		// $this->printthis($fare_quote);
		// $this->printthisexit($fare_quote);
		if(!empty($fare_quote)){
			$mainarr['taxi_name'] = $fare_quote['vCompany'];
			$options_arr=explode(',',$fare_quote['lOptions']);
			$fare=array();
			foreach ($options_arr as $key => $value) {
				$pos = strpos($value, $roundOption);
				if ($pos !== false) {
				    $fare=explode('|',$value);
				}
			}
			if($fare[0]==''){
				$data['msg'] = "No Fare Available";
				$response = json_encode($data);
				echo $response;exit;
			}
			$finalfare=(int)$fare[1];
			$mainarr['distance'] = ''; 
			$mainarr['duration'] = '';
			$per_Hr_fare = $fare_quote['fPerMinFare']*60;
			$finalfare = $finalfare + (($finalfare * $source_citydetail['fServicetax'])/100);
			$mainarr['total_fare'] = $source_citydetail['vCurrencySymbol'].(number_format($finalfare,2));
			$mainarr['total_fare_without_currency'] =number_format($finalfare,2);
			$mainarr['seating_capacity'] = $fare_quote['vSeatingCapacity'];
			$mainarr['source_address'] = $givenData['source_address'];
			$mainarr['destination_address'] = '';
			$mainarr['additional_per_KMs_Miles_rate'] = $source_citydetail['vCurrencySymbol'].(number_format($fare_quote['fPerMileFare'],2));
			$mainarr['additional_per_Hr_fare'] = $source_citydetail['vCurrencySymbol'].(number_format($per_Hr_fare,2));
			$mainarr['distanceUnit'] = $source_citydetail['eRadiusUnit'];
			$mainarr['fare_currency'] = $source_citydetail['vCurrencySymbol'];
			$mainarr['iCityId'] = $source_citydetail['iCityId'];
			$data['data'] = $mainarr;
			/*if ($booktype=='now' && $availableDriverCount == 0) {
				$data['msg'] = 'No Car Found in Pick UP Location';
			} else {
				$data['msg'] = 'Success';
			}*/

			$data['msg'] = 'Success';
			
		}else{
			$data['msg'] = "No Fare Available";
		}
		//---------------------------------------------------
		// Send Response
		$response = json_encode($data);
		echo $response;exit;
	}
	
	// Helper for Farequote option : Outstation By Duration
	function estimateOutRound($givenData,$source_citydetail,$startlatlong){
		$source_city_lat_long = explode('|', $source_citydetail['tCityLatLong']);
		$qurVar = ($source_citydetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
		$ridelocationArr = array('OutStationByDuration','BothByDuration');

		// check drivers available in source city
		$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$source_citydetail['fRadius'],$qurVar,$ridelocationArr,$givenData['car_id']);
		$availableDriverCount = 0;
		foreach ($drivers as $drkey => $driver) {
			$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($driver['iDriverId']);
			if($countRunningTrip==0){
				$availableDriverCount++;
			}
		}
		// Consider : MinimumMiles, perMileFare, selected day
		$roundOption = $givenData['vRoundOption'];
		$fare_quote = $this->webservices_model->get_fare_quote_details_new($source_citydetail['iCityId'],$givenData['car_id'],'OutStationByDuration','Round');
		if(!empty($fare_quote)){
			if ($givenData['destination_address'] && $roundOption !="") {
				$additionalFare=0;
				$destination_address = mysql_real_escape_string($givenData['destination_address']);
				$finishlatlong = $this->GetLatLongFromAddress($destination_address);
				// get distance
				$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);
				if($finalkmminarr!='none'){
					$mainarr['taxi_name'] = $fare_quote['vCompany'];
					if($source_citydetail['eRadiusUnit']=='KMs'){
						$distance = $finalkmminarr['distance_in_km']*2;
						$mainarr['distance'] = $distance.' KMS'; 
						$mainarr['distance_oneway'] = $finalkmminarr['distance_in_km'].' KMS'; 
					}else{
						$distance = $finalkmminarr['distance_in_mile']*2;
						$mainarr['distance'] = $distance.' MILES'; 
						$mainarr['distance_oneway'] = $finalkmminarr['distance_in_mile'].' MILES'; 
					}
					$totalTime = $finalkmminarr['duration_in_mins'] * 2;
					
					$mainarr['duration'] = $this->ConvertHrToMin($totalTime);
					$mainarr['duration_oneway'] = $this->ConvertHrToMin($finalkmminarr['duration_in_mins']);
					if($roundOption=='Full Day'){
						/*if($distance < $fare_quote['fMinimumKm']){
							$finalfare = ($fare_quote['fPerMileFare'] * $fare_quote['fMinimumKm']);
						}else {
							$finalfare = ($fare_quote['fPerMileFare'] * $distance);
						}*/
						$finalfare = ($fare_quote['fPerMileFare'] * $fare_quote['fMinimumKm']);
						$minFare=$finalfare;
						if($distance > $fare_quote['fMinimumKm']){
							$finalfare = $finalfare + ($fare_quote['fPerMileFare'] * ($distance - $fare_quote['fMinimumKm']));
						}
					}else{
						/*$optionArr = explode(' ', $roundOption);
						$dayPart = number_format(trim($optionArr[0]));
						if($distance < ($fare_quote['fMinimumKm'] * $dayPart)){
							$finalfare = ($fare_quote['fPerMileFare'] * $fare_quote['fMinimumKm'] * $dayPart);
						}else{
							$finalfare = ($fare_quote['fPerMileFare'] * $distance);
						}
						
						$finalfare = $finalfare + ($dayPart-1)*$fare_quote['fOvernightallowence'];*/
						$optionArr = explode(' ', $roundOption);

						$dayPart = number_format(trim($optionArr[0]));
						$finalfare = ($fare_quote['fPerMileFare'] * $fare_quote['fMinimumKm']) * $dayPart;
						if($distance > ($fare_quote['fMinimumKm'] * $dayPart)){
							$finalfare =$finalfare+ ($fare_quote['fPerMileFare'] * ($distance - ($fare_quote['fMinimumKm'] * $dayPart)));
						}
						$additionalFare = ($dayPart-1)*$fare_quote['fOvernightallowence'];
						$finalfare+=$additionalFare;
					}
					$per_Hr_fare = $fare_quote['fPerMinFare']*60;
					$finalfare = $finalfare + (($finalfare * $source_citydetail['fServicetax'])/100);
					$mainarr['total_fare'] = $source_citydetail['vCurrencySymbol'].(number_format($finalfare, 2));
					$mainarr['total_fare_without_currency'] =number_format($finalfare,2);
					$mainarr['seating_capacity'] = $fare_quote['vSeatingCapacity'];
					$mainarr['source_address'] = $givenData['source_address'];
					$mainarr['destination_address'] = $destination_address;
					$mainarr['additional_per_KMs_Miles_rate'] = $source_citydetail['vCurrencySymbol'].(number_format($fare_quote['fPerMileFare'],2));
					$mainarr['additional_per_Hr_fare'] = $source_citydetail['vCurrencySymbol'].(number_format($per_Hr_fare,2));
					$mainarr['distanceUnit'] = $source_citydetail['eRadiusUnit'];
					$mainarr['fare_currency'] = $source_citydetail['vCurrencySymbol'];
					$mainarr['iCityId'] = $source_citydetail['iCityId'];
					$mainarr['fMinimumKm'] = $fare_quote['fMinimumKm'];
					$data['data'] = $mainarr;
					/*if($givenData['booktype']=='now' && $availableDriverCount == 0){
						$data['msg'] = 'No Car Found in Pick UP Location';
					}else{
						$data['msg'] = 'Success';
					}*/
					$data['msg'] = 'Success';
					
				}else{
					$data['msg'] = "Failure";
				}
			}else{
				$data['msg'] = "Failure";
			}
		}else{
			$data['msg'] = "No Fare Available";
		}
		// Send Response
		$response = json_encode($data);
		echo $response;exit;
	}

	// Helper for Farequote option : Shuttle
	function estimateShuttle($givenData,$source_citydetail,$startlatlong){
		// - Source, Destination, total_adults [1 to 10], total_child [0 to 10], total_infant [0 to 10], total_pets [0 to 10],total_bags [0 to 8] 0 for 0-2, 1 for 3 and 2 -> 4 and so on. so for 10 it will be 8
		// - # of Standard Size Hand Bags between 0 to 10 : 0 as default, 0 -> 0-1 bag and 1 -> 2 and 2 -> 3 and so on. so for 10 it will be 9
		// - BOOK NOW AND BOOK LATER BUTTON button
		//-----------------------------------------
		$destination_address = mysql_real_escape_string($givenData['destination_address']);
		$finishlatlong = $this->GetLatLongFromAddress($destination_address);
		$source_address_city = $this->GetCityFromAddressOrLatLong($givenData['source_address'],'address');
		$sourceLatlong = $this->GetLatLongFromAddress($givenData['source_address']);
		$source_city_details=$this->webservices_model->getCityId($source_address_city);
		$FareDetails=$this->webservices_model->GetSourcecityFareDetails($source_city_details['iCityId'],$givenData['car_id']);
		if(count($FareDetails)==0){
			$data['msg'] = "Service Not Available In Your Pick UP Address City";
			$response = json_encode($data);
			echo $response;exit;
		}
		$distanceUnit = $FareDetails['eRadiusUnit'];
		$source_city_radius =$FareDetails['fRadius'];

		// Check Local or OutStation
		$check_distance = $this->GetMileKMFromLatLong($source_citydetail['tCityLatLong'],$finishlatlong);
		if ($distanceUnit=='Miles') {
			$ridelocation = ($check_distance['miles']<=$source_city_radius) ? "local" : "outstation" ;
		} else {
			$ridelocation = ($check_distance['kms']<=$source_city_radius) ? "local" : "outstation" ;
		}

		// get distance
		$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($sourceLatlong,$finishlatlong);
		if($finalkmminarr=='none'){
			$data['msg'] = "Failure";
			// Send Response
			$response = json_encode($data);
			echo $response;exit;
		}
		$currencySymbol = $source_citydetail['vCurrencySymbol'];
		$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
		$source_city_lat_long = explode('|', $source_citydetail['tCityLatLong']);
		
		$ridelocationArr[] = 'Shuttle';
		/*if ($ridelocation=='local') {
			$ridelocationArr[] = 'Both';
		} else {
			$ridelocationArr[] = 'Outstation';
			$ridelocationArr[] = 'Both';
		}*/

		// check drivers available in source city
		$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$source_city_radius,$qurVar,$ridelocationArr,$givenData['car_id']);
		$availableDriverCount = 0;
		foreach ($drivers as $drkey => $driver) {
			$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($driver['iDriverId']);
			if($countRunningTrip==0){
				$availableDriverCount++;
			}
		}
		//-----------------------------------------
		if(!empty($FareDetails)){
			$mainarr['taxi_name'] = $FareDetails['vCompany'];
			if ($distanceUnit=='KMs') {
				$distance = $finalkmminarr['distance_in_km'];
				$mainarr['distance'] = $distance.' KMS'; 
			} else {
				$distance = $finalkmminarr['distance_in_mile'];
				$mainarr['distance'] = $distance.' MILES'; 
			}
			$mainarr['duration'] = strtoupper($finalkmminarr['original_duration']);
			// Calculate fare
			if($FareDetails['fRadius']>=$distance){
				$finalfare=0;
				$finalfare+=$givenData['total_adults']*$FareDetails['fPerAdultFare'];
				$finalfare+=$givenData['total_child']*$FareDetails['fPerChildFare'];
				$finalfare+=$givenData['total_infant']*$FareDetails['fPerInfantFare'];
				$finalfare+=$givenData['total_pets']*$FareDetails['fPerPetFare'];
				if ($givenData['total_bags']>2) {
					$finalfare+=($givenData['total_bags']-2)*$FareDetails['fPerBagFare'];
				}
				if ($givenData['total_hand_bags']>1) {
					$finalfare+=($givenData['total_hand_bags']-1)*$FareDetails['fPerBagFare'];
				}
			}else{
				$finalfare=0;
				$adultfare=$distance*$FareDetails['fAdultFare'];
				$finalfare+=$adultfare*$givenData['total_adults'];
				$finalfare+=(($adultfare*$FareDetails['fChildPer'])/100)*$givenData['total_child'];
				$finalfare+=(($adultfare*$FareDetails['fInfantPer'])/100)*$givenData['total_infant'];
				$finalfare+=(($adultfare*$FareDetails['fPetPer'])/100)*$givenData['total_pets'];
				$finalfare+=(($adultfare*$FareDetails['fBagPer'])/100)*$givenData['total_bags'];

			}

			$mainarr['ridelocation'] = 'Shuttle';
			$mainarr['total_fare'] = $currencySymbol.(number_format($finalfare,2));
			$mainarr['total_fare_without_currency'] =number_format($finalfare,2);
			$mainarr['seating_capacity'] = $FareDetails['vSeatingCapacity'];
			$mainarr['source_address'] = $givenData['source_address'];
			$mainarr['destination_address'] = $givenData['destination_address'];
			$mainarr['fare_currency'] = $currencySymbol;
			$mainarr['iCityId'] = $source_citydetail['iCityId'];
			$data['data'] = $mainarr;
			if ($givenData['booktype']=='now' && $availableDriverCount == 0) {
				$data['msg'] = 'No Car Found in Pick UP Location';
			} else {
				$data['msg'] = 'Success';
			}
		}else{
			$data['msg'] = "No Fare Available";
		}
		// Send Response
		$response = json_encode($data);
		echo $response;exit;
	}

	// Helper for Farequote option : Fixed Ride
	function estimateFixedRide($givenData){
		$fixridedetail = $this->webservices_model->get_fix_ride_details($givenData['ride_id']);
		if (!empty($fixridedetail)){
			$source_citydetail = $this->webservices_model->get_one_city_details_byid($fixridedetail['iCityId']);
			$currencySymbol = $source_citydetail['vCurrencySymbol'];
			
			$distanceUnit = $source_citydetail['eRadiusUnit'];
			$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
			$source_city_lat_long = explode('|', $source_citydetail['tCityLatLong']);
			
			if ($fixridedetail['eFaretype']=='Local') {
				$ridelocationArr = array('Local','Both');
			} else {
				$ridelocationArr = array('Outstation','Both');
			}			
			// check drivers available in source city
			$drivers = $this->webservices_model->get_all_drivers_within_city($source_city_lat_long[0],$source_city_lat_long[1],$source_citydetail['fRadius'],$qurVar,$ridelocationArr,$givenData['car_id']);
			// $this->printthisexit($drivers);
			$availableDriverCount = 0;
			foreach ($drivers as $drkey => $driver) {
				$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($driver['iDriverId']);
				if($countRunningTrip==0){
					$availableDriverCount++;
				}
			}

			$fixfaredetail = $this->webservices_model->get_fix_ride_fare_details($givenData['ride_id'],$givenData['car_id']);
			if($fixfaredetail>0){
				$fixfaredetailWithTax =$fixfaredetail+($fixfaredetail*($source_citydetail['fServicetax']/100));
				$carDetail = $this->webservices_model->get_one_vehicle_detail_byId($givenData['car_id']);
				$mainarr['taxi_name'] = $carDetail['vCompany'];
				$mainarr['total_fare'] = $source_citydetail['vCurrencySymbol'].number_format($fixfaredetailWithTax,2);
				$mainarr['total_fare_without_currency'] =number_format($fixfaredetailWithTax,2);
				$mainarr['seating_capacity'] = $carDetail['vSeatingCapacity'];
				$mainarr['source_address'] = $fixridedetail['vPickUpLocation'];
				$mainarr['destination_address'] = $fixridedetail['vDropLocation'];
				$mainarr['duration'] = $this->ConvertHrToMin($fixridedetail['fDuration']);
				$mainarr['distance'] = $fixridedetail['fDistance']." ".strtoupper($source_citydetail['eRadiusUnit']);
				$mainarr['fare_currency'] = $source_citydetail['vCurrencySymbol'];
				$mainarr['iCityId'] = $source_citydetail['iCityId'];
				$data['data'] = $mainarr;
				if ($givenData['booktype']=='now' && $availableDriverCount == 0) {
					$data['msg'] = 'No Car Found in Pick UP Location';
				}else{
					$data['msg'] = 'Success';
				}
			}else {
				$data['msg'] = "No Fare Available";
			}
		}else{
			$data['msg'] = "Failure";
		}
		// Send Response
		$response = json_encode($data);
		echo $response;exit;
	}

	// BookRide new verson
	function BookRide(){
		// Taxi / CarPool / LocalByDuration / OutStationByDuration / Shuttle
		$givenData = $this->input->post();
		if($givenData['circleType'] && $givenData['rider_id'] && $givenData['booktype']){
			$status = $this->webservices_model->check_rider_exists($givenData['rider_id']);
			if($status=='exist'){
				if($givenData['circleType']=='Taxi'){
					// pera : rider_id, circleType[Taxi], source_address, destination_address, car_id, booktype[now/later], book_time [ if bootype = later ]
					$this->bookTaxi($givenData);
				}else if($givenData['circleType']=='CarPool'){
					// pera : rider_id, circleType[CarPool], source_address, destination_address, car_id, booktype[now/later], book_time [ if bootype = later ], eGenderPreference [Male/Female/Does Not Matter], eSmokingPreference [Smoking/Does Not Matter/Not Smoking]
					// - # of passanger with drop-down as 1 , 2, and 3 with 1 as default selection
					$this->bookCarPool($givenData);
				}else if($givenData['circleType']=='LocalByDuration'){
					// pera : rider_id, circleType[Local], source_address, vRoundOption, car_id, booktype[now/later], book_time [ if bootype = later ]
					$this->bookLocal($givenData);
				}else if($givenData['circleType']=='OutStationByDuration'){
					// pera : rider_id, circleType[OutStation], source_address, destination_address, vRoundOption, car_id, booktype[now/later], book_time [ if bootype = later ]
					$this->bookOutRound($givenData);
				}else if($givenData['circleType']=='Shuttle'){
					// pera : rider_id, circleType[Shuttle], source_address, destination_address, total_adults [1 to 10], total_child [0 to 10], total_infant [0 to 10], total_pets [0 to 10],total_bags [0 to 10], car_id, booktype[now/later], book_time [ if bootype = later ], newsletter_promo_code (optional), payment_type(Cash | Credit Card) if credit card then pass customer_credit_card_id
					$this->bookShuttle($givenData);
				}else if($givenData['circleType']=='FixRide'){
					$this->bookFixRide($givenData);
				}else {
					$data['msg'] = 'Failure'; // Failure 1
				}
			}else{
				$data['msg'] = 'Rider Not Exist';
			}
		}else{
			$data['msg'] = 'Failure'; // Failure 2
		}
		// Send Responce
		echo json_encode($data);exit;
	}

	// Helper for BookRide option : Taxi
	function bookTaxi($givenData){
		// rider_id, circleType[Taxi], source_address, destination_address, car_id, booktype[now/later], book_time [ if bootype = later ]
		if ($givenData['source_address'] && $givenData['destination_address'] && $givenData['car_id'] && $givenData['booktype'] && $givenData['ridelocation'] && $givenData['payment_type']) {
			
			$startlatlong = $this->GetLatLongFromAddress($givenData['source_address']);
			$source_lat_long_arr = explode('|', $startlatlong);
			$dLatitude=$source_lat_long_arr[0];
			$dLongitude=$source_lat_long_arr[1];
			$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKM($dLatitude,$dLongitude);
			$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMile($dLatitude,$dLongitude);
			// check within radius
			$check_km=($source_city_status_km['distance'] <= $source_city_status_km['fRadius']) ? $source_city_status_km : 0;
			$check_mile = ($source_city_status_mile['distance'] <= $source_city_status_mile['fRadius']) ? $source_city_status_mile : 0;
			if($check_km != 0 || $check_mile != 0){
				if($check_km != 0 && $check_mile == 0){
					$sourceCityDetail = $check_km;
				}else if($check_mile != 0 && $check_km == 0){
					$sourceCityDetail = $check_mile;
				}else if($check_mile != 0 && $check_km != 0){
					$distanceFromKM = $check_km['distance'];
					$distanceFromMile=$check_mile['distance']*1.609344;
					$sourceCityDetail = ($distanceFromKM < $distanceFromMile) ? $check_km : $check_mile ;
				}else{
					$Data['msg'] = "Failure"; // Failure 3
					echo json_encode($Data);
					exit;
				}
			}else{
				$Data['msg'] = "Failure"; // Failure 4
				echo json_encode($Data);
				exit;
			}
			//-------------------------------
			$source = mysql_real_escape_string($givenData['source_address']);
			$dest = mysql_real_escape_string($givenData['destination_address']);
			// $source_address_city = $this->GetCityFromAddressOrLatLong($source,'address');
			$source_address_city = $sourceCityDetail['vCity'];
			$destination_city = $this->GetCityFromAddressOrLatLong($dest,'address');
			$finishlatlong = $this->GetLatLongFromAddress($dest);
			$onecitydetail = $this->webservices_model->get_one_city_details($source_address_city);
			$distanceUnit = $onecitydetail['eRadiusUnit'];
			$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
			$source_city_lat_long = explode('|', $onecitydetail['tCityLatLong']);
			$source_city_radius =$onecitydetail['fRadius'];
			$payment_type = $givenData['payment_type']; // Cash / Credit Card
			$booktype=$givenData['booktype'];
			$trip_Details_arr = array();
			
			$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
			$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
			if ($booktype=='now') {
				$book_time = $trip_Details_arr['dBookingDate'];
				$trip_Details_arr['eBookType'] = 'Now';
			} else {
				$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
				$trip_Details_arr['eBookType'] = 'Later';
			}
			$trip_Details_arr['dTripDate'] = $book_time;
			if ($givenData['ridelocation']=='Local') {
				$ridelocationArr[] = 'Local';
				$ridelocationArr[] = 'Both';
				$trip_Details_arr['eTripLocation'] = 'Local';
			} else if ($givenData['ridelocation']=='Outstation') {
				$ridelocationArr[] = 'Outstation';
				$ridelocationArr[] = 'Both';
				$trip_Details_arr['eTripLocation'] = 'Outstation';
			}else{
				$data['msg'] = 'Failure'; // Failure 5
				echo json_encode($data);exit;
			}
			$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);
			
			if ($distanceUnit=='Miles') {
				$distance = $finalkmminarr['distance_in_mile'];
			} else {
				$distance = $finalkmminarr['distance_in_km'];
			}
			$transaction_arr = array();

			$trip_Details_arr['eDistanceUnit'] = $onecitydetail['eRadiusUnit'];
			if($payment_type=='Cash'){
				$trip_Details_arr['ePaymentType'] = 'Cash';
				$trip_Details_arr['iCustomerCreditCardId'] = 0;
			}else {
				$trip_Details_arr['ePaymentType'] = 'Credit Card';
				$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
			}
			$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
			$trip_Details_arr['tDestinationAddressLatLong'] = $finishlatlong;
			$trip_Details_arr['tRouteImgURL'] = "";
			$trip_Details_arr['iRideId'] = 0;
			$trip_Details_arr['eType'] = 'Unfixride';
			$trip_Details_arr['eTripType'] = 'One Way';
			
			// code for newsletter promo code discount
			$newsletter_promo_code = '';
			if($givenData['newsletter_promo_code']){
				$newsletter_promo_code = $givenData['newsletter_promo_code'];

				// delete promocode from final table
				$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
				$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
				// end of code for delete promocode from final table

				$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);

				$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
				if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
					if($promo_data){
						$newsletter_promo_code = $givenData['newsletter_promo_code'];
						$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
					}else {
						$newsletter_promo_code = '';    
						$trip_Details_arr['iPromotionCodeId'] = '';
					}
				}else {
					$newsletter_promo_code = '';    
					$trip_Details_arr['iPromotionCodeId'] = '';
				}
			}

			// code for invitation code discounts
			$invitation_total_final_dis = 0;
			$invitecnt = 0;
			$invitemainarr = array();
			$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
			$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
			if($invitesinglecode){
				$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
				foreach ($invitefollowers as $key => $value) {
					$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
					if(empty($invitefollowersdata)){
						$invitearr = array();
						$invitearr['iClientId'] = $givenData['rider_id']; 
						$invitearr['iPromocodeUserId'] = $value['iClientId']; 
						$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
						$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
						array_push($invitemainarr, $iInvitationPromocodeId);
						$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
						$invitecnt++;
					}
				}
				
				if($invitecnt==0){
					$invitation_total_final_dis = 0;
				}   
			}
			// end of code for invitation code discounts
			// 1. Local
			if($givenData['ridelocation']=='Local') {
				// Consider : BaseFare, MinimumFare, MinimumMiles, perMileFare, PerMinuteFare
				$fare_quote = $this->webservices_model->get_fare_quote_details_new($onecitydetail['iCityId'],$givenData['car_id'],'Local','One Way');
				if($distance < $fare_quote['fMinimumKm']){
					$finalfare = $fare_quote['fThreeKmFare'];
					$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
					$trip_Details_arr['fPerMileFare'] = '';
					$trip_Details_arr['fPerMinFare'] = '';
					$trip_Details_arr['fBaseFare'] = '';
					$trip_Details_arr['MinimumFare'] = $finalfare;
					$trip_Details_arr['fServiceTax'] = $fServiceTax;
					$finalfare = $finalfare + $fServiceTax;
					$trip_Details_arr['dSubtotalPayment'] = $finalfare;
				}else {
					$perMile = 0;
					$finalfare = $fare_quote['fBaseFare'];
					$current_hr_min=date('H:i', strtotime($book_time));
					if ($onecitydetail['ePicksurchargestatus']=='Yes') {
						$vPicktimefrom = date('H:i', strtotime($onecitydetail['vPicktimefrom']));
						$vPicktimeto = date('H:i', strtotime($onecitydetail['vPicktimeto']));
						if ($this->timeisBetween($vPicktimefrom,$vPicktimeto,$current_hr_min)){
							$perMile =$fare_quote['fPerMileFare']+(($fare_quote['fPerMileFare']*$fare_quote['fPeaktimesurcharge'])/100);
						}
					}
					if ($onecitydetail['eNightsurchargestatus']=='Yes') {
						$vNighttimefrom = date('H:i', strtotime($onecitydetail['vNighttimefrom']));
						$vNighttimeto = date('H:i', strtotime($onecitydetail['vNighttimeto']));
						if ($this->timeisBetween($vNighttimefrom,$vNighttimeto,$current_hr_min)){
							$perMile =$fare_quote['fPerMileFare']+($fare_quote['fPerMileFare']*($fare_quote['fNighttimesurcharge'])/ 100);
						}
					}
					$perMile = ($perMile==0) ? $fare_quote['fPerMileFare'] : $perMile;
					
					$totalfareinmiles = ($perMile * $distance);
					$totalfareinmins = ($fare_quote['fPerMinFare'] * $finalkmminarr['duration_in_mins']);
					$finalfare = $finalfare + ($totalfareinmiles+$totalfareinmins);
					$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
					$finalfare = $finalfare + $fServiceTax;

					$trip_Details_arr['fPerMileFare'] = $totalfareinmiles;
					$trip_Details_arr['fPerMinFare'] = $totalfareinmins;
					$trip_Details_arr['fBaseFare'] = $fare_quote['fBaseFare'];
					$trip_Details_arr['MinimumFare'] = $fare_quote['fThreeKmFare'];
					$trip_Details_arr['fServiceTax'] = $fServiceTax;
					$trip_Details_arr['dSubtotalPayment'] = $finalfare;
				}
				$trip_Details_arr['fTotalMinute'] = $finalkmminarr['duration_in_mins'];
				$trip_Details_arr['fDistance'] = $distance;
			}
			// 3. Outstation
			if($givenData['ridelocation']=='Outstation') {
				// Consider : MinimumFare, MinimumMiles, perMileFare
				$fare_quote=$this->webservices_model->get_fare_quote_details_new($onecitydetail['iCityId'],$givenData['car_id'],'OutStation','One Way');
				
				if($distance <= $fare_quote['fMinimumKm']){
					$finalfare = $fare_quote['fThreeKmFare'];
				}else {
					$finalfare = ($fare_quote['fPerMileFare'] * $distance);
					// $finalfare = $finalfare + (($finalfare * $onecitydetail['fServicetax'])/100);
					if ($fare_quote['fThreeKmFare'] > $finalfare) {
						$finalfare = $fare_quote['fThreeKmFare'];
					}
				}
				$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
				$trip_Details_arr['fPerMileFare'] = '';
				$trip_Details_arr['fPerMinFare'] = '';
				$trip_Details_arr['fBaseFare'] = '';
				$finalfare = $finalfare + $fServiceTax;
				$trip_Details_arr['fServiceTax'] = $fServiceTax;
				$trip_Details_arr['dSubtotalPayment'] = $finalfare;

				$trip_Details_arr['MinimumFare'] = $fare_quote['fThreeKmFare'];
				$trip_Details_arr['fTotalMinute'] = $finalkmminarr['duration_in_mins'];
				$trip_Details_arr['fDistance'] = $distance;
			}
			//*************** Discount calculations **********************
				$finaltotalinvitationbookdiscount = 0;
				if($invitation_total_final_dis){
					$finalpay = $finalfare;
					$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
					$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
					$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
				}

				$finaltotalpromobookdiscount = 0;
				if($newsletter_promo_code){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);

					if($promo_data['eDiscountType']=='Percentage'){
						$percentage_promo = $promo_data['fDiscount'];
						$finalpay = $finalfare;
						$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
					}else if($promo_data['eDiscountType']=='Amount'){
						$finaltotalpromobookdiscount = $promo_data['fDiscount'];
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
					}
				}

				$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
				if($finalinvitebookdiscount>$finalfare){
					$trip_Details_arr['fFinalPayment'] = '0.00';
				}else {
					$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
				}
			//*************** Discount calculations **********************
			$trip_Details_arr['iClientId'] = $givenData['rider_id'];
			$trip_Details_arr['vPickupLocation'] = $source;
			$trip_Details_arr['vDestinationLocation'] = $dest;
			$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
			$trip_Details_arr['ePaymentStatus'] = 'In progress';
			$trip_Details_arr['eStatus'] = 'Pending';
			$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
			$trip_Details_arr['eDriverAssign'] = 'No';
			// ********************** save trip details arr **********************
			
			$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
			if($iTripId>0){
				if(count($invitemainarr)>0){
					$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
				}
						
				// send notification to rider
				$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
				if($rider_device_details){
					$pushNotificationData['action']  = 'sendNotification';
					$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
					$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
					$pushNotificationData['eUserType'] = "Rider";
					$datariderpush = $this->pushNotification($pushNotificationData);
				}
				// end of code send notification to rider
				// code for send notification to driver / VO
				$circleType='Taxi';
				$tripData['ridelocation']=$givenData['ridelocation'];
				$tripData['source']=$source_address_city;
				$tripData['destination']=$destination_city;
				$tripData['iTripId']=$iTripId;
				$tripData['lat_long']=$source_city_lat_long;
				$tripData['source_radius']=$source_city_radius;
				$tripData['qurVar']=$qurVar;
				$tripData['ridelocationArr']=$ridelocationArr;
				$tripData['car_id']=$givenData['car_id'];
				$tripData['iCityId']=$trip_Details_arr['iCityId'];
				$tripData['book_time']=$book_time;
				if ($booktype=='now'){
					$this->notifyDriverOnBooking($circleType,$tripData);
				}else{
					$tripData['source_address']=$givenData['source_address'];
					$tripData['destination_address']=$givenData['destination_address'];
					$this->notifyOwnerOnBooking($circleType,$tripData);
				}
				$data['msg'] = 'Success';
			}else {
				$data['msg'] = 'Failure'; // Failure 6
			}
			//************************************************************************************************************
		} else {
			$data['msg'] = 'Failure'; // Failure 7
		}
		// Send Responce
		echo json_encode($data);exit;
	}

	// Helper for BookRide option : CarPool
	function bookCarPool($givenData){
		// pera : rider_id, circleType[CarPool], source_address, destination_address, car_id, booktype[now/later], book_time [ if bootype = later ], eGenderPreference [Male/Female/Does Not Matter], eSmokingPreference [Yes/Does Not Matter/No], passanger_count [# of passanger]
		if ($givenData['source_address'] && $givenData['destination_address'] && $givenData['car_id'] && $givenData['booktype'] && $givenData['ridelocation'] && $givenData['payment_type'] && $givenData['eGenderPreference'] && $givenData['eSmokingPreference'] && $givenData['passanger_count']) {

			// Get sorce, dest city by helper using address
			$source = $this->getCityFromLatLongAddress('Address',$givenData['source_address']);
			$onecitydetail = $this->webservices_model->get_one_city_details_byid($source['iCityId']);
			$startlatlong = $source['address_lat_long'];
			$dest = $this->getCityFromLatLongAddress('Address',$givenData['destination_address']);
			$finishlatlong = $dest['address_lat_long'];
			$destCityDetail = $this->webservices_model->get_one_city_details_byid($dest['iCityId']);

			$distanceUnit = $onecitydetail['eRadiusUnit'];
			$payment_type = $givenData['payment_type']; // Cash / Credit Card
			$booktype=$givenData['booktype'];
			$trip_Details_arr = array();
			
			$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
			$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
			if ($booktype=='now') {
				$book_time = $trip_Details_arr['dBookingDate'];
				$trip_Details_arr['eBookType'] = 'Now';
			} else {
				$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
				$trip_Details_arr['eBookType'] = 'Later';
			}
			$trip_Details_arr['dTripDate'] = $book_time;
			if ($givenData['ridelocation']=='CarPoolLocal') {
				$ridelocationArr[] = 'CarPoolLocal';
				$ridelocationArr[] = 'CarPoolBoth';
			} else if ($givenData['ridelocation']=='CarPoolOutstation') {
				$ridelocationArr[] = 'CarPoolOutstation';
				$ridelocationArr[] = 'CarPoolBoth';
			}else{
				$data['msg'] = 'Failure';
				echo json_encode($data);exit;
			}
			$trip_Details_arr['eTripLocation'] = $givenData['ridelocation'];
			$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);
			
			if ($distanceUnit=='Miles') {
				$distance = $finalkmminarr['distance_in_mile'];
			} else {
				$distance = $finalkmminarr['distance_in_km'];
			}
			$transaction_arr = array();

			$trip_Details_arr['eDistanceUnit'] = $onecitydetail['eRadiusUnit'];
			if($payment_type=='Cash'){
				$trip_Details_arr['ePaymentType'] = 'Cash';
				$trip_Details_arr['iCustomerCreditCardId'] = 0;
			}else {
				$trip_Details_arr['ePaymentType'] = 'Credit Card';
				$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
			}
			$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
			$trip_Details_arr['tDestinationAddressLatLong'] = $finishlatlong;
			$trip_Details_arr['tRouteImgURL'] = "";
			$trip_Details_arr['iRideId'] = 0;
			$trip_Details_arr['eType'] = 'Unfixride';
			$trip_Details_arr['eTripType'] = 'One Way';
			
			// code for newsletter promo code discount
			$newsletter_promo_code = '';
			if($givenData['newsletter_promo_code']){
				$newsletter_promo_code = $givenData['newsletter_promo_code'];

				// delete promocode from final table
				$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
				$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
				// end of code for delete promocode from final table

				$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);

				$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
				if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
					if($promo_data){
						$newsletter_promo_code = $givenData['newsletter_promo_code'];
						$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
					}else {
						$newsletter_promo_code = '';    
						$trip_Details_arr['iPromotionCodeId'] = '';
					}
				}else {
					$newsletter_promo_code = '';    
					$trip_Details_arr['iPromotionCodeId'] = '';
				}
			}

			// code for invitation code discounts
			$invitation_total_final_dis = 0;
			$invitecnt = 0;
			$invitemainarr = array();
			$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
			$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
			if($invitesinglecode){
				$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
				foreach ($invitefollowers as $key => $value) {
					$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
					if(empty($invitefollowersdata)){
						$invitearr = array();
						$invitearr['iClientId'] = $givenData['rider_id']; 
						$invitearr['iPromocodeUserId'] = $value['iClientId']; 
						$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
						$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
						array_push($invitemainarr, $iInvitationPromocodeId);
						$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
						$invitecnt++;
					}
				}
				
				if($invitecnt==0){
					$invitation_total_final_dis = 0;
				}   
			}
			// end of code for invitation code discounts
			
			// Consider : MinimumFare, perMileFare
			$fare_quote=$this->webservices_model->get_fare_quote_details_new($onecitydetail['iCityId'],$givenData['car_id'],$givenData['ridelocation'],'One Way');
			$finalfare = ($fare_quote['fPerMileFare'] * $distance);
			if ($fare_quote['fThreeKmFare'] > $finalfare) {
				$finalfare = $fare_quote['fThreeKmFare'];
			}
			$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
			$finalfare = $finalfare + $fServiceTax;
			$trip_Details_arr['fServiceTax'] = $fServiceTax;
			$trip_Details_arr['fPerMileFare'] = '';
			$trip_Details_arr['fPerMinFare'] = '';
			$trip_Details_arr['fBaseFare'] = '';
			$trip_Details_arr['fQuoteFare'] = $finalfare;
			$trip_Details_arr['dSubtotalPayment'] = $finalfare;
			$trip_Details_arr['MinimumFare'] = $fare_quote['fThreeKmFare'];
			$trip_Details_arr['fTotalMinute'] = $finalkmminarr['duration_in_mins'];
			$trip_Details_arr['fDistance'] = $distance;
			//*************** Discount calculations **********************
				$finaltotalinvitationbookdiscount = 0;
				if($invitation_total_final_dis){
					$finalpay = $finalfare;
					$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
					$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
					$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
				}

				$finaltotalpromobookdiscount = 0;
				if($newsletter_promo_code){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);

					if($promo_data['eDiscountType']=='Percentage'){
						$percentage_promo = $promo_data['fDiscount'];
						$finalpay = $finalfare;
						$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
					}else if($promo_data['eDiscountType']=='Amount'){
						$finaltotalpromobookdiscount = $promo_data['fDiscount'];
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
					}
				}

				$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
				if($finalinvitebookdiscount>$finalfare){
					$trip_Details_arr['fFinalPayment'] = '0.00';
				}else {
					$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
				}
			//*************** Discount calculations **********************
			$trip_Details_arr['iClientId'] = $givenData['rider_id'];
			$trip_Details_arr['vPickupLocation'] = mysql_real_escape_string($givenData['source_address']);
			$trip_Details_arr['vDestinationLocation'] = mysql_real_escape_string($givenData['destination_address']);
			$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
			$trip_Details_arr['ePaymentStatus'] = 'In progress';
			$trip_Details_arr['eStatus'] = 'Pending';
			$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
			$trip_Details_arr['eDriverAssign'] = 'No';
			$trip_Details_arr['eGenderPreference'] = $givenData['eGenderPreference'];
			$trip_Details_arr['eSmokingPreference'] = $givenData['eSmokingPreference'];
			// ********************** save trip details arr **********************
			
			$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
			if($iTripId>0){
				if(count($invitemainarr)>0){
					$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
				}
						
				// send notification to rider
				$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
				if($rider_device_details){
					$pushNotificationData['action']  = 'sendNotification';
					$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
					$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
					$pushNotificationData['eUserType'] = "Rider";
					$datariderpush = $this->pushNotification($pushNotificationData);
				}
				// end of code send notification to rider
				// code for send notification to driver / VO
				$circleType='CarPool';
				$tripData['ridelocation']=$givenData['ridelocation'];
				$tripData['iTripId']=$iTripId;
				$tripData['source_radius']=$onecitydetail['fSourceRadius'];
				$tripData['dest_radius']=$destCityDetail['fDestinationRadius'];
				$tripData['qurVarSource']=($onecitydetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
				$tripData['qurVardest']=($destCityDetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
				$tripData['ridelocationArr']=$ridelocationArr;
				$tripData['car_id']=$givenData['car_id'];
				$tripData['eGenderPreference']=$givenData['eGenderPreference'];
				$tripData['eSmokingPreference']=$givenData['eSmokingPreference'];
				$tripData['pickLatLong'] = $startlatlong;
				$tripData['destLatLong'] = $finishlatlong;
				// if ($booktype=='now'){
					$tripData['book_time']=$book_time;
					$tripData['source_address']=$givenData['source_address'];
					$tripData['destination_address']=$givenData['destination_address'];
					$this->notifyDriverOnBooking($circleType,$tripData);
				// }else{
					// $tripData['book_time']=$book_time;
					// $tripData['source_address']=$givenData['source_address'];
					// $tripData['destination_address']=$givenData['destination_address'];
					// $this->notifyOwnerOnBooking($circleType,$tripData);
				// }
				$data['msg'] = 'Success';
			}else{
				$data['msg'] = 'Failure';
			}
		}else{
			$data['msg'] = 'Failure';
		}
		// Send Responce
		echo json_encode($data);exit;
	}
	function bookCarPool_bk($givenData){
		// pera : rider_id, circleType[CarPool], source_address, destination_address, car_id, booktype[now/later], book_time [ if bootype = later ], eGenderPreference [Male/Female/Does Not Matter], eSmokingPreference [Smoking/Does Not Matter/Not Smoking], passanger_count [# of passanger]
		if ($givenData['source_address'] && $givenData['destination_address'] && $givenData['car_id'] && $givenData['booktype'] && $givenData['ridelocation'] && $givenData['payment_type'] && $givenData['eGenderPreference'] && $givenData['eSmokingPreference'] && $givenData['passanger_count']) {

			$startlatlong = $this->GetLatLongFromAddress($givenData['source_address']);
			$source_lat_long_arr = explode('|', $startlatlong);
			$dLatitude=$source_lat_long_arr[0];
			$dLongitude=$source_lat_long_arr[1];
			$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKM($dLatitude,$dLongitude);
			$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMile($dLatitude,$dLongitude);
			if ($source_city_status_km || $source_city_status_mile) {
				if ($source_city_status_km) {
					$distanceFromKM = $source_city_status_km['distance'];
				}
				if ($source_city_status_mile) {
					$distanceFromMile=$source_city_status_mile['distance']*1.609344;
				}
				if(isset($distanceFromMile)){
					if(isset($distanceFromKM)){
						if ($distanceFromKM > $distanceFromMile) {
							$sourceCityDetail = $source_city_status_mile;
						} else {
							$sourceCityDetail = $source_city_status_km;
						}
					}else{
						$sourceCityDetail = $source_city_status_mile;
					}
				}else{
					$sourceCityDetail = $source_city_status_km;
				}
			}else{
				$Data['msg'] = "Failure";
				echo json_encode($Data);
				exit;
			}
			
			$source = mysql_real_escape_string($givenData['source_address']);
			$dest = mysql_real_escape_string($givenData['destination_address']);
			// $source_address_city = $this->GetCityFromAddressOrLatLong($source,'address');
			$source_address_city = $sourceCityDetail['vCity'];
			$destination_city = $this->GetCityFromAddressOrLatLong($dest,'address');
			$finishlatlong = $this->GetLatLongFromAddress($dest);
			$onecitydetail = $this->webservices_model->get_one_city_details($source_address_city);
			$distanceUnit = $onecitydetail['eRadiusUnit'];
			$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
			$source_city_lat_long = explode('|', $onecitydetail['tCityLatLong']);
			$source_city_radius =$onecitydetail['fRadius'];
			$payment_type = $givenData['payment_type']; // Cash / Credit Card
			$booktype=$givenData['booktype'];
			$trip_Details_arr = array();
			
			$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
			$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
			if ($booktype=='now') {
				$book_time = $trip_Details_arr['dBookingDate'];
				$trip_Details_arr['eBookType'] = 'Now';
			} else {
				$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
				$trip_Details_arr['eBookType'] = 'Later';
			}
			$trip_Details_arr['dTripDate'] = $book_time;
			if ($givenData['ridelocation']=='CarPoolLocal') {
				$ridelocationArr[] = 'CarPoolLocal';
				$ridelocationArr[] = 'CarPoolBoth';
			} else if ($givenData['ridelocation']=='CarPoolOutstation') {
				$ridelocationArr[] = 'CarPoolOutstation';
				$ridelocationArr[] = 'CarPoolBoth';
			}else{
				$data['msg'] = 'Failure';
				echo json_encode($data);exit;
			}
			$trip_Details_arr['eTripLocation'] = $givenData['ridelocation'];
			$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);
			
			if ($distanceUnit=='Miles') {
				$distance = $finalkmminarr['distance_in_mile'];
			} else {
				$distance = $finalkmminarr['distance_in_km'];
			}
			$transaction_arr = array();

			$trip_Details_arr['eDistanceUnit'] = $onecitydetail['eRadiusUnit'];
			if($payment_type=='Cash'){
				$trip_Details_arr['ePaymentType'] = 'Cash';
				$trip_Details_arr['iCustomerCreditCardId'] = 0;
			}else {
				$trip_Details_arr['ePaymentType'] = 'Credit Card';
				$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
			}
			$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
			$trip_Details_arr['tDestinationAddressLatLong'] = $finishlatlong;
			$trip_Details_arr['tRouteImgURL'] = "";
			$trip_Details_arr['iRideId'] = 0;
			$trip_Details_arr['eType'] = 'Unfixride';
			$trip_Details_arr['eTripType'] = 'One Way';
			
			// code for newsletter promo code discount
			$newsletter_promo_code = '';
			if($givenData['newsletter_promo_code']){
				$newsletter_promo_code = $givenData['newsletter_promo_code'];

				// delete promocode from final table
				$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
				$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
				// end of code for delete promocode from final table

				$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);

				$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
				if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
					if($promo_data){
						$newsletter_promo_code = $givenData['newsletter_promo_code'];
						$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
					}else {
						$newsletter_promo_code = '';    
						$trip_Details_arr['iPromotionCodeId'] = '';
					}
				}else {
					$newsletter_promo_code = '';    
					$trip_Details_arr['iPromotionCodeId'] = '';
				}
			}

			// code for invitation code discounts
			$invitation_total_final_dis = 0;
			$invitecnt = 0;
			$invitemainarr = array();
			$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
			$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
			if($invitesinglecode){
				$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
				foreach ($invitefollowers as $key => $value) {
					$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
					if(empty($invitefollowersdata)){
						$invitearr = array();
						$invitearr['iClientId'] = $givenData['rider_id']; 
						$invitearr['iPromocodeUserId'] = $value['iClientId']; 
						$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
						$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
						array_push($invitemainarr, $iInvitationPromocodeId);
						$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
						$invitecnt++;
					}
				}
				
				if($invitecnt==0){
					$invitation_total_final_dis = 0;
				}   
			}
			// end of code for invitation code discounts
			
			// Consider : MinimumFare, perMileFare
			$fare_quote=$this->webservices_model->get_fare_quote_details_new($onecitydetail['iCityId'],$givenData['car_id'],$givenData['ridelocation'],'One Way');
			$finalfare = ($fare_quote['fPerMileFare'] * $distance);
			if ($fare_quote['fThreeKmFare'] > $finalfare) {
				$finalfare = $fare_quote['fThreeKmFare'];
			}
			$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
			$finalfare = $finalfare + $fServiceTax;
			$trip_Details_arr['fServiceTax'] = $fServiceTax;
			$trip_Details_arr['fPerMileFare'] = '';
			$trip_Details_arr['fPerMinFare'] = '';
			$trip_Details_arr['fBaseFare'] = '';
			$trip_Details_arr['fQuoteFare'] = $finalfare;
			$trip_Details_arr['dSubtotalPayment'] = $finalfare;
			$trip_Details_arr['MinimumFare'] = $fare_quote['fThreeKmFare'];
			$trip_Details_arr['fTotalMinute'] = $finalkmminarr['duration_in_mins'];
			$trip_Details_arr['fDistance'] = $distance;
			//*************** Discount calculations **********************
				$finaltotalinvitationbookdiscount = 0;
				if($invitation_total_final_dis){
					$finalpay = $finalfare;
					$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
					$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
					$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
				}

				$finaltotalpromobookdiscount = 0;
				if($newsletter_promo_code){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);

					if($promo_data['eDiscountType']=='Percentage'){
						$percentage_promo = $promo_data['fDiscount'];
						$finalpay = $finalfare;
						$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
					}else if($promo_data['eDiscountType']=='Amount'){
						$finaltotalpromobookdiscount = $promo_data['fDiscount'];
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
					}
				}

				$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
				if($finalinvitebookdiscount>$finalfare){
					$trip_Details_arr['fFinalPayment'] = '0.00';
				}else {
					$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
				}
			//*************** Discount calculations **********************
			$trip_Details_arr['iClientId'] = $givenData['rider_id'];
			$trip_Details_arr['vPickupLocation'] = $source;
			$trip_Details_arr['vDestinationLocation'] = $dest;
			$trip_Details_arr['eGenderPreference'] = $givenData['eGenderPreference'];
			$trip_Details_arr['eSmokingPreference'] = $givenData['eSmokingPreference'];
			$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
			$trip_Details_arr['ePaymentStatus'] = 'In progress';
			$trip_Details_arr['eStatus'] = 'Pending';
			$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
			$trip_Details_arr['eDriverAssign'] = 'No';
			// ********************** save trip details arr **********************
			
			$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
			if($iTripId>0){
				if(count($invitemainarr)>0){
					$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
				}
						
				// send notification to rider
				$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
				if($rider_device_details){
					$pushNotificationData['action']  = 'sendNotification';
					$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
					$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
					$pushNotificationData['eUserType'] = "Rider";
					$datariderpush = $this->pushNotification($pushNotificationData);
				}
				// end of code send notification to rider
				// code for send notification to driver / VO
				$circleType='Taxi';
				$tripData['ridelocation']=$givenData['ridelocation'];
				$tripData['source']=$source_address_city;
				$tripData['destination']=$destination_city;
				$tripData['iTripId']=$iTripId;
				$tripData['lat_long']=$source_city_lat_long;
				$tripData['source_radius']=$source_city_radius;
				$tripData['qurVar']=$qurVar;
				$tripData['ridelocationArr']=$ridelocationArr;
				$tripData['car_id']=$givenData['car_id'];
				// if ($booktype=='now'){
					$tripData['book_time']=$book_time;
					$tripData['source_address']=$givenData['source_address'];
					$tripData['destination_address']=$givenData['destination_address'];
					$this->notifyDriverOnBooking($circleType,$tripData);
				// }else{
					// $tripData['book_time']=$book_time;
					// $tripData['source_address']=$givenData['source_address'];
					// $tripData['destination_address']=$givenData['destination_address'];
					// $this->notifyOwnerOnBooking($circleType,$tripData);
				// }
				$data['msg'] = 'Success';
			}else{
				$data['msg'] = 'Failure';
			}
		}else{
			$data['msg'] = 'Failure';
		}
		// Send Responce
		echo json_encode($data);exit;
	}

	// Helper for BookRide option : Local & Round
	function bookLocal($givenData){
		// rider_id, circleType[Local], source_address, vRoundOption, car_id, booktype[now/later], book_time [ if bootype = later ]
		if ($givenData['source_address'] && $givenData['vRoundOption'] && $givenData['car_id'] && $givenData['booktype'] && $givenData['payment_type']) {

			//-------------------------------------------------------------
			$startlatlong = $this->GetLatLongFromAddress($givenData['source_address']);
			$source_lat_long_arr = explode('|', $startlatlong);
			$dLatitude=$source_lat_long_arr[0];
			$dLongitude=$source_lat_long_arr[1];
			$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKM($dLatitude,$dLongitude);
			$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMile($dLatitude,$dLongitude);
			//-------------------------------
			// check within radius
			$check_km=($source_city_status_km['distance'] <= $source_city_status_km['fRadius']) ? $source_city_status_km : 0;
			$check_mile = ($source_city_status_mile['distance'] <= $source_city_status_mile['fRadius']) ? $source_city_status_mile : 0;
			if($check_km != 0 || $check_mile != 0){
				if($check_km != 0 && $check_mile == 0){
					$sourceCityDetail = $check_km;
				}else if($check_mile != 0 && $check_km == 0){
					$sourceCityDetail = $check_mile;
				}else if($check_mile != 0 && $check_km != 0){
					$distanceFromKM = $check_km['distance'];
					$distanceFromMile=$check_mile['distance']*1.609344;
					$sourceCityDetail = ($distanceFromKM < $distanceFromMile) ? $check_km : $check_mile ;
				}else{
					$Data['msg'] = "Failure";
					echo json_encode($Data);
					exit;
				}
			}else{
				$Data['msg'] = "Failure";
				echo json_encode($Data);
				exit;
			}
			//-------------------------------
			$source = mysql_real_escape_string($givenData['source_address']);
			$source_address_city = $sourceCityDetail['vCity'];
			$onecitydetail = $this->webservices_model->get_one_city_details($source_address_city);

			$distanceUnit = $onecitydetail['eRadiusUnit'];
			$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
			$source_city_lat_long = explode('|', $onecitydetail['tCityLatLong']);
			$source_city_radius =$onecitydetail['fRadius'];
			$payment_type = $givenData['payment_type']; // Cash / Credit Card
			$booktype=$givenData['booktype'];
			$trip_Details_arr = array();
			
			$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
			$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
			if ($booktype=='now') {
				$book_time = $trip_Details_arr['dBookingDate'];
				$trip_Details_arr['eBookType'] = 'Now';
			} else {
				$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
				$trip_Details_arr['eBookType'] = 'Later';
			}
			$trip_Details_arr['dTripDate'] = $book_time;
			$ridelocationArr = array('LocalByDuration','BothByDuration');
			$trip_Details_arr['eTripLocation'] = 'LocalByDuration';
			$trip_Details_arr['eDistanceUnit'] = $onecitydetail['eRadiusUnit'];
			if($payment_type=='Cash'){
				$trip_Details_arr['ePaymentType'] = 'Cash';
				$trip_Details_arr['iCustomerCreditCardId'] = 0;
			}else {
				$trip_Details_arr['ePaymentType'] = 'Credit Card';
				$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
			}
			$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
			$trip_Details_arr['tDestinationAddressLatLong'] = '';
			$trip_Details_arr['tRouteImgURL'] = "";
			$trip_Details_arr['iRideId'] = 0;
			$trip_Details_arr['eType'] = 'Unfixride';
			$trip_Details_arr['eTripType'] = 'Round';
			$trip_Details_arr['eOutstationNotification'] = 'No';
			$trip_Details_arr['vRoundOption'] = $givenData['vRoundOption'];
			
			// code for newsletter promo code discount
			$newsletter_promo_code = '';
			if($givenData['newsletter_promo_code']){
				$newsletter_promo_code = $givenData['newsletter_promo_code'];

				// delete promocode from final table
				$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
				$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
				// end of code for delete promocode from final table

				$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);

				$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
				if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
					if($promo_data){
						$newsletter_promo_code = $givenData['newsletter_promo_code'];
						$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
					}else {
						$newsletter_promo_code = '';    
						$trip_Details_arr['iPromotionCodeId'] = '';
					}
				}else {
					$newsletter_promo_code = '';    
					$trip_Details_arr['iPromotionCodeId'] = '';
				}
			}
			
			// code for invitation code discounts
			$invitation_total_final_dis = 0;
			$invitecnt = 0;
			$invitemainarr = array();
			$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
			$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
			if($invitesinglecode){
				$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
				foreach ($invitefollowers as $key => $value) {
					$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
					if(empty($invitefollowersdata)){
						$invitearr = array();
						$invitearr['iClientId'] = $givenData['rider_id']; 
						$invitearr['iPromocodeUserId'] = $value['iClientId']; 
						$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
						$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
						array_push($invitemainarr, $iInvitationPromocodeId);
						$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
						$invitecnt++;
					}
				}
				
				if($invitecnt==0){
					$invitation_total_final_dis = 0;
				}   
			}
			// end of code for invitation code discounts

			// Fare Calcualtions
			$fare_quote = $this->webservices_model->get_fare_quote_details_new($sourceCityDetail['iCityId'],$givenData['car_id'],'LocalByDuration','Round');
			$options_arr=explode(',',$fare_quote['lOptions']);
			$fare=array();
			foreach ($options_arr as $key => $value) {
				$pos = strpos($value, $givenData['vRoundOption']);
				if ($pos !== false) {
				    $fare=explode('|',$value);
				}
			}
			$finalfare=(int)$fare[1];

			// $finalfare = $minFare + (($minFare * $onecitydetail['fServicetax'])/100);
			//$finalfare = $minFare;
			$trip_Details_arr['MinimumFare'] = $finalfare;
			$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
			$finalfare = $finalfare + $fServiceTax;
			$trip_Details_arr['fServiceTax'] = $fServiceTax;
			$trip_Details_arr['fPerMileFare'] = $fare_quote['fPerMileFare'];
			$trip_Details_arr['fPerMinFare'] = $fare_quote['fPerMinFare'];
			$trip_Details_arr['fBaseFare'] = '';
			$trip_Details_arr['dSubtotalPayment'] = $finalfare;
			$trip_Details_arr['fTotalMinute'] = '';
			$trip_Details_arr['fDistance'] = '';
			
			//*************** Discount calculations **********************
				$finaltotalinvitationbookdiscount = 0;
				if($invitation_total_final_dis){
					$finalpay = $finalfare;
					$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
					$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
					$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
				}

				$finaltotalpromobookdiscount = 0;
				if($newsletter_promo_code){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
					if($promo_data['eDiscountType']=='Percentage'){
						$percentage_promo = $promo_data['fDiscount'];
						$finalpay = $finalfare;
						$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
					}else if($promo_data['eDiscountType']=='Amount'){
						$finaltotalpromobookdiscount = $promo_data['fDiscount'];
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
					}
				}

				$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
				if($finalinvitebookdiscount>$finalfare){
					$trip_Details_arr['fFinalPayment'] = '0.00';
				}else {
					$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
				}
			//*************** Discount calculations **********************
			$trip_Details_arr['iClientId'] = $givenData['rider_id'];
			$trip_Details_arr['vPickupLocation'] = $source;
			$trip_Details_arr['vDestinationLocation'] = '';
			$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
			$trip_Details_arr['ePaymentStatus'] = 'In progress';
			$trip_Details_arr['eStatus'] = 'Pending';
			$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
			$trip_Details_arr['eDriverAssign'] = 'No';
			// ********************** save trip details arr **********************
			
			$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
			if($iTripId>0){
				if(count($invitemainarr)>0){
					$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
				}
						
				// send notification to rider
				$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
				if($rider_device_details){
					$pushNotificationData['action']  = 'sendNotification';
					$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
					$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
					$pushNotificationData['eUserType'] = "Rider";
					$datariderpush = $this->pushNotification($pushNotificationData);
				}
				// end of code send notification to rider
				// code for send notification to driver / VO
				$circleType='LocalByDuration';
				$tripData['ridelocation']='Local';
				$tripData['source']=$source_address_city;
				$tripData['iTripId']=$iTripId;
				$tripData['lat_long']=$source_city_lat_long;
				$tripData['source_radius']=$source_city_radius;
				$tripData['qurVar']=$qurVar;
				$tripData['ridelocationArr']=$ridelocationArr;
				$tripData['car_id']=$givenData['car_id'];
				$tripData['iCityId']=$trip_Details_arr['iCityId'];
				if ($booktype!='now'){
					//$this->notifyDriverOnBooking($circleType,$tripData);
					//}else{
					$tripData['book_time']=$book_time;
					$tripData['source_address']=$givenData['source_address'];
					$tripData['vRoundOption']=$givenData['vRoundOption'];
				}
				$this->notifyOwnerOnBooking($circleType,$tripData);
				$data['msg'] = 'Success';
			}else {
				$data['msg'] = 'Failure';
			}
			//************************************************************************************************************
		} else {
			$data['msg'] = 'Failure';
		}
		// Send Responce
		echo json_encode($data);exit;
	}

	// Helper for BookRide option : OutStation & Round
	function bookOutRound($givenData){
		// rider_id, circleType[Taxi], source_address, destination_address, car_id, booktype[now/later], book_time [ if bootype = later ]
		if ($givenData['source_address'] && $givenData['destination_address'] && $givenData['car_id'] && $givenData['booktype'] && $givenData['vRoundOption'] && $givenData['payment_type']) {

			//-------------------------------------------------------------
			$startlatlong = $this->GetLatLongFromAddress($givenData['source_address']);
			$source_lat_long_arr = explode('|', $startlatlong);
			$dLatitude=$source_lat_long_arr[0];
			$dLongitude=$source_lat_long_arr[1];
			$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKM($dLatitude,$dLongitude);
			$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMile($dLatitude,$dLongitude);
			//-------------------------------
			// check within radius
			$check_km=($source_city_status_km['distance'] <= $source_city_status_km['fRadius']) ? $source_city_status_km : 0;
			$check_mile = ($source_city_status_mile['distance'] <= $source_city_status_mile['fRadius']) ? $source_city_status_mile : 0;
			if($check_km != 0 || $check_mile != 0){
				if($check_km != 0 && $check_mile == 0){
					$sourceCityDetail = $check_km;
				}else if($check_mile != 0 && $check_km == 0){
					$sourceCityDetail = $check_mile;
				}else if($check_mile != 0 && $check_km != 0){
					$distanceFromKM = $check_km['distance'];
					$distanceFromMile=$check_mile['distance']*1.609344;
					$sourceCityDetail = ($distanceFromKM < $distanceFromMile) ? $check_km : $check_mile ;
				}else{
					$Data['msg'] = "Failure";
					echo json_encode($Data);
					exit;
				}
			}else{
				$Data['msg'] = "Failure";
				echo json_encode($Data);
				exit;
			}
			//-------------------------------
			$source = mysql_real_escape_string($givenData['source_address']);
			$dest = mysql_real_escape_string($givenData['destination_address']);
			// $source_address_city = $this->GetCityFromAddressOrLatLong($source,'address');
			$source_address_city = $sourceCityDetail['vCity'];
			$destination_city = $this->GetCityFromAddressOrLatLong($dest,'address');
			
			$finishlatlong = $this->GetLatLongFromAddress($dest);
			$onecitydetail = $this->webservices_model->get_one_city_details($source_address_city);

			$distanceUnit = $onecitydetail['eRadiusUnit'];
			$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
			$source_city_lat_long = explode('|', $onecitydetail['tCityLatLong']);
			$source_city_radius =$onecitydetail['fRadius'];
			$payment_type = $givenData['payment_type']; // Cash / Credit Card
			$booktype=$givenData['booktype'];
			$trip_Details_arr = array();
			
			$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
			$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
			if ($booktype=='now') {
				$book_time = $trip_Details_arr['dBookingDate'];
				$trip_Details_arr['eBookType'] = 'Now';
			} else {
				$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
				$trip_Details_arr['eBookType'] = 'Later';
			}
			$trip_Details_arr['dTripDate'] = $book_time;
			
			$ridelocationArr = array('OutStationByDuration','BothByDuration');
			$trip_Details_arr['eTripLocation'] = 'OutStationByDuration';
			
			$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);
			
			if ($distanceUnit=='Miles') {
				$distance = $finalkmminarr['distance_in_mile'];
			} else {
				$distance = $finalkmminarr['distance_in_km'];
			}
			$trip_Details_arr['eDistanceUnit'] = $onecitydetail['eRadiusUnit'];
			if($payment_type=='Cash'){
				$trip_Details_arr['ePaymentType'] = 'Cash';
				$trip_Details_arr['iCustomerCreditCardId'] = 0;
			}else {
				$trip_Details_arr['ePaymentType'] = 'Credit Card';
				$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
			}
			$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
			$trip_Details_arr['tDestinationAddressLatLong'] = $finishlatlong;
			$trip_Details_arr['tRouteImgURL'] = "";
			$trip_Details_arr['iRideId'] = 0;
			$trip_Details_arr['eType'] = 'Unfixride';
			$trip_Details_arr['eTripType'] = 'Round';
			$trip_Details_arr['vRoundOption'] = $givenData['vRoundOption'];
			
			// code for newsletter promo code discount
			$newsletter_promo_code = '';
			if($givenData['newsletter_promo_code']){
				$newsletter_promo_code = $givenData['newsletter_promo_code'];

				// delete promocode from final table
				$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
				$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
				// end of code for delete promocode from final table

				$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);

				$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
				if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
					if($promo_data){
						$newsletter_promo_code = $givenData['newsletter_promo_code'];
						$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
					}else {
						$newsletter_promo_code = '';    
						$trip_Details_arr['iPromotionCodeId'] = '';
					}
				}else {
					$newsletter_promo_code = '';    
					$trip_Details_arr['iPromotionCodeId'] = '';
				}
			}

			// code for invitation code discounts
			$invitation_total_final_dis = 0;
			$invitecnt = 0;
			$invitemainarr = array();
			$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
			$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
			if($invitesinglecode){
				$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
				foreach ($invitefollowers as $key => $value) {
					$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
					if(empty($invitefollowersdata)){
						$invitearr = array();
						$invitearr['iClientId'] = $givenData['rider_id']; 
						$invitearr['iPromocodeUserId'] = $value['iClientId']; 
						$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
						$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
						array_push($invitemainarr, $iInvitationPromocodeId);
						$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
						$invitecnt++;
					}
				}
				
				if($invitecnt==0){
					$invitation_total_final_dis = 0;
				}   
			}
			// end of code for invitation code discounts
			
			// ------------------------- Fare calculation -------------------------
			// Consider : MinimumMiles, perMileFare, selected day
			$fare_quote=$this->webservices_model->get_fare_quote_details_new($onecitydetail['iCityId'],$givenData['car_id'],'OutStationByDuration','Round');
			
			$roundOption = $givenData['vRoundOption'];

			$additionalFare=0;
			
			$totalDistance = $distance*2;
			$totalTime = $finalkmminarr['duration_in_mins'] * 2;
			
			if($roundOption=='Full Day'){
				$finalfare = ($fare_quote['fPerMileFare'] * $fare_quote['fMinimumKm']);
				$minFare=$finalfare;
				if($totalDistance > $fare_quote['fMinimumKm']){
					$finalfare = $finalfare + ($fare_quote['fPerMileFare'] * ($totalDistance - $fare_quote['fMinimumKm']));
				}
				
				$trip_Details_arr['dToDate']= date('Y-m-d H:i:s', strtotime("+1 day", strtotime($book_time)));
			}else{
				$optionArr = explode(' ', $roundOption);
				$dayPart = number_format(trim($optionArr[0]));
				$finalfare = ($fare_quote['fPerMileFare'] * $fare_quote['fMinimumKm']) * $dayPart;
				$minFare=$finalfare;
				if($totalDistance > ($fare_quote['fMinimumKm'] * $dayPart)){
					$finalfare =$finalfare+ ($fare_quote['fPerMileFare'] * ($totalDistance - ($fare_quote['fMinimumKm'] * $dayPart)));
				}else{
					$totalDistance=$fare_quote['fMinimumKm'];
				}
				
				$trip_Details_arr['dToDate']= date('Y-m-d H:i:s', strtotime("+".$dayPart." day", strtotime($book_time)));

				$additionalFare = ($dayPart-1)*$fare_quote['fOvernightallowence'];
			}
			$finalfare =$finalfare+$additionalFare;

			$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
			$finalfare = $finalfare + $fServiceTax;
			// $finalfare = $finalfare + (($finalfare * $onecitydetail['fServicetax'])/100);
			
			$trip_Details_arr['fPerMileFare'] = $fare_quote['fPerMileFare'];
			$trip_Details_arr['fPerMinFare'] = $fare_quote['fPerMinFare'];
			$trip_Details_arr['fBaseFare'] = '';
			$trip_Details_arr['fServiceTax'] = $fServiceTax;
			$trip_Details_arr['dSubtotalPayment'] = $finalfare;
			$trip_Details_arr['MinimumFare'] = $minFare;
			$trip_Details_arr['fTotalMinute'] = $totalTime;
			$trip_Details_arr['fDistance'] = $totalDistance;
			// ------------------------- Fare calculation Ends -------------------------
			//*************** Discount calculations **********************
				$finaltotalinvitationbookdiscount = 0;
				if($invitation_total_final_dis){
					$finalpay = $finalfare;
					$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
					$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
					$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
				}

				$finaltotalpromobookdiscount = 0;
				if($newsletter_promo_code){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);

					if($promo_data['eDiscountType']=='Percentage'){
						$percentage_promo = $promo_data['fDiscount'];
						$finalpay = $finalfare;
						$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
					}else if($promo_data['eDiscountType']=='Amount'){
						$finaltotalpromobookdiscount = $promo_data['fDiscount'];
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
					}
				}

				$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
				if($finalinvitebookdiscount>$finalfare){
					$trip_Details_arr['fFinalPayment'] = '0.00';
				}else {
					$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
				}
			//*************** Discount calculations **********************
			$trip_Details_arr['iClientId'] = $givenData['rider_id'];
			$trip_Details_arr['vPickupLocation'] = $source;
			$trip_Details_arr['vDestinationLocation'] = $dest;
			$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
			$trip_Details_arr['ePaymentStatus'] = 'In progress';
			$trip_Details_arr['eStatus'] = 'Pending';
			$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
			$trip_Details_arr['eDriverAssign'] = 'No';
			// ********************** save trip details arr **********************
			
			$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
			if($iTripId>0){
				if(count($invitemainarr)>0){
					$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
				}
						
				// send notification to rider
				$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
				if($rider_device_details){
					$pushNotificationData['action']  = 'sendNotification';
					$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
					$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
					$pushNotificationData['eUserType'] = "Rider";
					$datariderpush = $this->pushNotification($pushNotificationData);
				}
				// end of code send notification to rider
				// code for send notification to driver / VO
				$circleType='OutStationByDuration';
				$tripData['ridelocation']=$givenData['ridelocation'];
				$tripData['source']=$source_address_city;
				$tripData['destination']=$destination_city;
				$tripData['iTripId']=$iTripId;
				$tripData['lat_long']=$source_city_lat_long;
				$tripData['source_radius']=$source_city_radius;
				$tripData['qurVar']=$qurVar;
				$tripData['ridelocationArr']=$ridelocationArr;
				$tripData['car_id']=$givenData['car_id'];
				$tripData['book_time']=$book_time;
				$tripData['source_address']=$givenData['source_address'];
				$tripData['destination_address']=$givenData['destination_address'];
				$tripData['iCityId']=$trip_Details_arr['iCityId'];
					//if ($booktype != 'now'){
					//$this->notifyDriverOnBooking($circleType,$tripData);
					//}else{
				$this->notifyOwnerOnBooking($circleType,$tripData);
				//}
				$data['msg'] = 'Success';
			}else {
				$data['msg'] = 'Failure';
			}
			//************************************************************************************************************
		} else {
			$data['msg'] = 'Failure';
		}
		// Send Responce
		echo json_encode($data);exit;
	}

	// Helper for BookRide option : Shuttle
	function bookShuttle($givenData){
		// rider_id, circleType[Shuttle], source_address, destination_address, total_adults [1 to 10], total_child [0 to 10], total_infant [0 to 10], total_pets [0 to 10],total_bags [0 to 10], car_id, booktype[now/later], book_time [ if bootype = later ], newsletter_promo_code (optional), payment_type(Cash | Credit Card) if credit card then pass customer_credit_card_id
		if ($givenData['source_address'] && $givenData['destination_address'] && $givenData['total_adults'] && $givenData['car_id'] && $givenData['booktype'] && $givenData['payment_type']){
			$source_address_city = $this->GetCityFromAddressOrLatLong($givenData['source_address'],'address');
			$source_city_details=$this->webservices_model->getCityId($source_address_city);
			$FareDetails=$this->webservices_model->GetSourcecityFareDetails($source_city_details['iCityId'],$givenData['car_id']);
			if(count($FareDetails)==0){
				$Data['msg'] = "Failure";
				echo json_encode($Data);
				exit;
			}
			$startlatlong = $this->GetLatLongFromAddress($givenData['source_address']);
			$source = mysql_real_escape_string($givenData['source_address']);
			$dest = mysql_real_escape_string($givenData['destination_address']);
			$destination_city = $this->GetCityFromAddressOrLatLong($dest,'address');
			$finishlatlong = $this->GetLatLongFromAddress($dest);
			$onecitydetail = $this->webservices_model->get_one_city_details($source_address_city);
			$distanceUnit = $FareDetails['eRadiusUnit'];
			$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;

			$source_city_lat_long = explode('|', $onecitydetail['tCityLatLong']);
			$source_city_radius =$FareDetails['fRadius'];
			$payment_type = $givenData['payment_type']; // Cash / Credit Card
			$booktype=$givenData['booktype'];
			$trip_Details_arr = array();
			$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
			$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
			if ($booktype=='now') {
				$book_time = $trip_Details_arr['dBookingDate'];
				$trip_Details_arr['eBookType'] = 'Now';
			} else {
				$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
				$trip_Details_arr['eBookType'] = 'Later';
			}
			$trip_Details_arr['dTripDate'] = $book_time;
			$ridelocationArr[] = 'Shuttle';
			$trip_Details_arr['eTripLocation'] = 'Shuttle';
			$finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong($startlatlong,$finishlatlong);
			if ($distanceUnit=='Miles') {
				$distance = $finalkmminarr['distance_in_mile'];
			} else {
				$distance = $finalkmminarr['distance_in_km'];
			}
			$trip_Details_arr['eDistanceUnit'] = $FareDetails['eRadiusUnit'];
			if($payment_type=='Cash'){
				$trip_Details_arr['ePaymentType'] = 'Cash';
				$trip_Details_arr['iCustomerCreditCardId'] = 0;
			}else {
				$trip_Details_arr['ePaymentType'] = 'Credit Card';
				$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
			}
			$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
			$trip_Details_arr['tDestinationAddressLatLong'] = $finishlatlong;
			$trip_Details_arr['tRouteImgURL'] = "";
			$trip_Details_arr['iRideId'] = 0;
			$trip_Details_arr['eType'] = 'Unfixride';
			$trip_Details_arr['eTripType'] = 'One Way';

			// code for newsletter promo code discount
			$newsletter_promo_code = '';
			if($givenData['newsletter_promo_code']){
				$newsletter_promo_code = $givenData['newsletter_promo_code'];
				// delete promocode from final table
				$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
				$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
				// end of code for delete promocode from final table
				$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);
				$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
				if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
					if($promo_data){
						$newsletter_promo_code = $givenData['newsletter_promo_code'];
						$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
					}else {
						$newsletter_promo_code = '';    
						$trip_Details_arr['iPromotionCodeId'] = '';
					}
				}else {
					$newsletter_promo_code = '';    
					$trip_Details_arr['iPromotionCodeId'] = '';
				}
			}

			// code for invitation code discounts
			$invitation_total_final_dis = 0;
			$invitecnt = 0;
			$invitemainarr = array();
			$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
			$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
			if($invitesinglecode){
				$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
				foreach ($invitefollowers as $key => $value) {
					$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
					if(empty($invitefollowersdata)){
						$invitearr = array();
						$invitearr['iClientId'] = $givenData['rider_id']; 
						$invitearr['iPromocodeUserId'] = $value['iClientId']; 
						$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
						$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
						array_push($invitemainarr, $iInvitationPromocodeId);
						$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
						$invitecnt++;
					}
				}
				if($invitecnt==0){
					$invitation_total_final_dis = 0;
				}   
			}
			// end of code for invitation code discounts
			// Fare Calculations
			$finalfare=0;
			if($FareDetails['fRadius']>=$distance){
				$finalfare+=$givenData['total_adults']*$FareDetails['fPerAdultFare'];
				$finalfare+=$givenData['total_child']*$FareDetails['fPerChildFare'];
				$finalfare+=$givenData['total_infant']*$FareDetails['fPerInfantFare'];
				$finalfare+=$givenData['total_pets']*$FareDetails['fPerPetFare'];
				if ($givenData['total_bags']>2) {
					$finalfare+=($givenData['total_bags']-2)*$FareDetails['fPerBagFare'];
				}
				if ($givenData['total_hand_bags']>1) {
					$finalfare+=($givenData['total_hand_bags']-1)*$FareDetails['fPerBagFare'];
				}
			}else{
				$adultfare=$distance*$FareDetails['fAdultFare'];
				$finalfare+=$adultfare*$givenData['total_adults'];
				$finalfare+=(($adultfare*$FareDetails['fChildPer'])/100)*$givenData['total_child'];
				$finalfare+=(($adultfare*$FareDetails['fInfantPer'])/100)*$givenData['total_infant'];
				$finalfare+=(($adultfare*$FareDetails['fPetPer'])/100)*$givenData['total_pets'];
				$finalfare+=(($adultfare*$FareDetails['fBagPer'])/100)*$givenData['total_bags'];
			}
			$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);


			$trip_Details_arr['fPerMileFare'] = '';
			$trip_Details_arr['fPerMinFare'] = '';
			$trip_Details_arr['fBaseFare'] = '';
			$trip_Details_arr['MinimumFare'] = $finalfare;
			$trip_Details_arr['fServiceTax'] = $fServiceTax;
			$finalfare = $finalfare + $fServiceTax;
			$trip_Details_arr['dSubtotalPayment'] = $finalfare;
			$trip_Details_arr['fTotalMinute'] = $finalkmminarr['duration_in_mins'];
			$trip_Details_arr['fDistance'] = $distance;
			// string should be : total_adults,total_child,total_infant,total_pets,total_bags,total_hand_bags|fPerAdultFare,fPerChildFare,fPerInfantFare,fPerPetFare,fPerBagFare,fPerBagFare
			$trip_Details_arr['vShuttleInfo'] = $givenData['total_adults'].','.$givenData['total_child'].','.$givenData['total_infant'].','.$givenData['total_pets'].','.$givenData['total_bags'].','.$givenData['total_hand_bags'].'|'.$FareDetails['fPerAdultFare'].','.$FareDetails['fPerChildFare'].','.$FareDetails['fPerInfantFare'].','.$FareDetails['fPerPetFare'].','.$FareDetails['fPerBagFare'].','.$FareDetails['fPerBagFare'];
			//*************** Discount calculations **********************
				$finaltotalinvitationbookdiscount = 0;
				if($invitation_total_final_dis){
					$finalpay = $finalfare;
					$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
					$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
					$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
				}

				$finaltotalpromobookdiscount = 0;
				if($newsletter_promo_code){
					$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);

					if($promo_data['eDiscountType']=='Percentage'){
						$percentage_promo = $promo_data['fDiscount'];
						$finalpay = $finalfare;
						$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
					}else if($promo_data['eDiscountType']=='Amount'){
						$finaltotalpromobookdiscount = $promo_data['fDiscount'];
						$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
					}
				}

				$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
				if($finalinvitebookdiscount>$finalfare){
					$trip_Details_arr['fFinalPayment'] = '0.00';
				}else {
					$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
				}
			//*************** Discount calculations **********************
			$trip_Details_arr['iClientId'] = $givenData['rider_id'];
			$trip_Details_arr['vPickupLocation'] = $source;
			$trip_Details_arr['vDestinationLocation'] = $dest;
			$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
			$trip_Details_arr['ePaymentStatus'] = 'In progress';
			$trip_Details_arr['eStatus'] = 'Pending';
			$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
			$trip_Details_arr['eDriverAssign'] = 'No';
			$trip_Details_arr['eOutstationNotification'] = 'No';
			// ********************** save trip details arr **********************
			$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
			if($iTripId>0){
				if(count($invitemainarr)>0){
					$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
				}
				// send notification to rider
				$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
				if($rider_device_details){
					$pushNotificationData['action']  = 'sendNotification';
					$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
					$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
					$pushNotificationData['eUserType'] = "Rider";
					$datariderpush = $this->pushNotification($pushNotificationData);
				}
				// end of code send notification to rider
				// code for send notification to driver / VO
				$circleType='Shuttle';
				$tripData['source']=$source_address_city;
				$tripData['destination']=$destination_city;
				$tripData['iTripId']=$iTripId;
				$tripData['lat_long']=$source_city_lat_long;
				$tripData['source_radius']=$source_city_radius;
				$tripData['qurVar']=$qurVar;
				$tripData['ridelocationArr']=$ridelocationArr;
				$tripData['car_id']=$givenData['car_id'];
				$tripData['book_time']=$book_time;
				$tripData['iCityId']=$trip_Details_arr['iCityId'];
				if ($booktype=='now'){
					$this->notifyDriverOnBooking($circleType,$tripData);
				}else{
					$tripData['source_address']=$givenData['source_address'];
					$tripData['destination_address']=$givenData['destination_address'];
					$this->notifyOwnerOnBooking($circleType,$tripData);
				}
				$data['msg'] = 'Success';
			}else {
				$data['msg'] = 'Failure';
			}
		} else {
			$data['msg'] = 'Failure';
		}
		// Send Responce
		echo json_encode($data);exit;
	}

	// Helper for BookRide option : FixRide
	/*function bookFixRide($givenData){
		if ($givenData['ride_id'] && $givenData['payment_type']){
			$fixridedetail = $this->webservices_model->get_fix_ride_details($givenData['ride_id']);
			if (!empty($fixridedetail)){
				$fixfaredetail = $this->webservices_model->get_fix_ride_fare_details($givenData['ride_id'],$givenData['car_id']);
				if($fixfaredetail==0){
					$data['msg'] = 'Failure';
					echo json_encode($data);exit;
				}
				$startlatlong = $this->GetLatLongFromAddress($fixridedetail['vPickUpLocation']);
				$finishlatlong = $this->GetLatLongFromAddress($fixridedetail['vDropLocation']);
				$onecitydetail = $this->webservices_model->get_one_city_details_byid($fixridedetail['iCityId']);
				$distanceUnit = $onecitydetail['eRadiusUnit'];
				$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
				$source_city_lat_long = explode('|', $onecitydetail['tCityLatLong']);
				$source_city_radius =$onecitydetail['fRadius'];
				$payment_type = $givenData['payment_type']; // Cash / Credit Card
				$booktype=$givenData['booktype'];
				$trip_Details_arr = array();
				
				$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
				$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
				if ($booktype=='now') {
					$book_time = $trip_Details_arr['dBookingDate'];
					$trip_Details_arr['eBookType'] = 'Now';
				} else {
					$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
					$trip_Details_arr['eBookType'] = 'Later';
				}
				$trip_Details_arr['dTripDate'] = $book_time;
				$currencySymbol = $onecitydetail['vCurrencySymbol'];
				if ($fixridedetail['eFaretype']=='Local') {
					$ridelocationArr = array('Local','Both');
				} else {
					$ridelocationArr = array('Outstation','Both');
				}
				$payment_type = $givenData['payment_type']; // Cash / Credit Card
				$trip_Details_arr['eTripLocation'] = $fixridedetail['eFaretype'];
				$trip_Details_arr['eDistanceUnit'] = $onecitydetail['eRadiusUnit'];
				if($payment_type=='Cash'){
					$trip_Details_arr['ePaymentType'] = 'Cash';
					$trip_Details_arr['iCustomerCreditCardId'] = 0;
				}else {
					$trip_Details_arr['ePaymentType'] = 'Credit Card';
					$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
				}
				$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
				$trip_Details_arr['tDestinationAddressLatLong'] = $finishlatlong;
				$trip_Details_arr['tRouteImgURL'] = "";
				$trip_Details_arr['iRideId'] = $givenData['ride_id'];
				$trip_Details_arr['eType'] = 'Fixride';
				$trip_Details_arr['eTripType'] = $fixridedetail['eTripType'];
				// code for newsletter promo code discount
				$newsletter_promo_code = '';
				if($givenData['newsletter_promo_code']){
					$newsletter_promo_code = $givenData['newsletter_promo_code'];
					// delete promocode from final table
					$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
					$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
					// end of code for delete promocode from final table
					$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);
					$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
					if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
						$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
						if($promo_data){
							$newsletter_promo_code = $givenData['newsletter_promo_code'];
							$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
						}else {
							$newsletter_promo_code = '';    
							$trip_Details_arr['iPromotionCodeId'] = '';
						}
					}else {
						$newsletter_promo_code = '';    
						$trip_Details_arr['iPromotionCodeId'] = '';
					}
				}

				// code for invitation code discounts
				$invitation_total_final_dis = 0;
				$invitecnt = 0;
				$invitemainarr = array();
				$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
				$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
				if($invitesinglecode){
					$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
					foreach ($invitefollowers as $key => $value) {
						$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
						if(empty($invitefollowersdata)){
							$invitearr = array();
							$invitearr['iClientId'] = $givenData['rider_id']; 
							$invitearr['iPromocodeUserId'] = $value['iClientId']; 
							$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
							$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
							array_push($invitemainarr, $iInvitationPromocodeId);
							$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
							$invitecnt++;
						}
					}
					if($invitecnt==0){
						$invitation_total_final_dis = 0;
					}   
				}
				// end of code for invitation code discounts
				// Fare Calculations
				$finalfare = $fixfaredetail;
				$trip_Details_arr['fPerMileFare'] = '';
				$trip_Details_arr['fPerMinFare'] = '';
				$trip_Details_arr['fBaseFare'] = '';
				$trip_Details_arr['MinimumFare'] = $fixfaredetail;
				//
				$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
				$trip_Details_arr['fServiceTax'] = $fServiceTax;
				$finalfare = $finalfare + $fServiceTax;

				$trip_Details_arr['dSubtotalPayment'] = $finalfare;
				$trip_Details_arr['fTotalMinute'] = $fixridedetail['fDuration'];
				$trip_Details_arr['fDistance'] = $fixridedetail['fDistance'];
				//*************** Discount calculations **********************
					$finaltotalinvitationbookdiscount = 0;
					if($invitation_total_final_dis){
						$finalpay = $finalfare;
						$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
						$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
						$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
					}

					$finaltotalpromobookdiscount = 0;
					if($newsletter_promo_code){
						$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);

						if($promo_data['eDiscountType']=='Percentage'){
							$percentage_promo = $promo_data['fDiscount'];
							$finalpay = $finalfare;
							$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
							$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
						}else if($promo_data['eDiscountType']=='Amount'){
							$finaltotalpromobookdiscount = $promo_data['fDiscount'];
							$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
						}
					}

					$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
					if($finalinvitebookdiscount>$finalfare){
						$trip_Details_arr['fFinalPayment'] = '0.00';
					}else {
						$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
					}
				//*************** Discount calculations **********************
				$trip_Details_arr['iClientId'] = $givenData['rider_id'];
				$trip_Details_arr['vPickupLocation'] = $fixridedetail['vPickUpLocation'];
				$trip_Details_arr['vDestinationLocation'] = $fixridedetail['vDropLocation'];
				$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
				$trip_Details_arr['ePaymentStatus'] = 'In progress';
				$trip_Details_arr['eStatus'] = 'Pending';
				$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
				$trip_Details_arr['eDriverAssign'] = 'No';
				$trip_Details_arr['eOutstationNotification'] = 'No';
				// ********************** save trip details arr **********************
				
				$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
				if($iTripId>0){
					if(count($invitemainarr)>0){
						$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
					}
							
					// send notification to rider
					$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
					if($rider_device_details){
						$pushNotificationData['action']  = 'sendNotification';
						$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
						$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
						$pushNotificationData['eUserType'] = "Rider";
						$datariderpush = $this->pushNotification($pushNotificationData);
					}
					// end of code send notification to rider
					// code for send notification to driver / VO
					$circleType='FixRide';
					$tripData['iTripId']=$iTripId;
					$tripData['lat_long']=$source_city_lat_long;
					$tripData['source_radius']=$source_city_radius;
					$tripData['qurVar']=$qurVar;
					$tripData['ridelocationArr']=$ridelocationArr;
					$tripData['car_id']=$givenData['car_id'];
					$tripData['book_time']=$book_time;
					$tripData['iCityId']=$trip_Details_arr['iCityId'];
					if ($booktype=='now'){
						$this->notifyDriverOnBooking($circleType,$tripData);
					}else{
						$tripData['source_address']=$fixridedetail['vPickUpLocation'];
						$tripData['destination_address']=$fixridedetail['vDropLocation'];
						$this->notifyOwnerOnBooking($circleType,$tripData);
					}
					$data['msg'] = 'Success';
				}else {
					$data['msg'] = 'Failure';
				}
			}else{
				$data['msg'] = "Failure";
			}
		} else {
			$data['msg'] = 'Failure';
		}
		// Send Responce
		echo json_encode($data);exit;
	}*/
	function bookFixRide($givenData){

		//need to pass 
		// total_adults,total_child,total_infant,total_pets,total_bags,total_hand_bags
		if ($givenData['ride_id'] && $givenData['payment_type']){
			$fixridedetail = $this->webservices_model->get_fix_ride_details($givenData['ride_id']);
			if($fixridedetail['eFaretype']=='Shuttle'){
				$startlatlong = $this->GetLatLongFromAddress($fixridedetail['vPickUpLocation']);
				$finishlatlong = $this->GetLatLongFromAddress($fixridedetail['vDropLocation']);
				$givenData['car_id']=$this->webservices_model->getShuttleID();
				$onecitydetail = $this->webservices_model->get_one_city_details_byid($fixridedetail['iCityId']);
				$distanceUnit = $onecitydetail['eRadiusUnit'];
				$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
				$source_city_lat_long = explode('|', $onecitydetail['tCityLatLong']);
				$source_city_radius =$onecitydetail['fRadius'];
				$payment_type = $givenData['payment_type']; // Cash / Credit Card
				$booktype=$givenData['booktype'];
				$trip_Details_arr = array();
				
				$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
				$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
				if ($booktype=='now') {
					$book_time = $trip_Details_arr['dBookingDate'];
					$trip_Details_arr['eBookType'] = 'Now';
				} else {
					$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
					$trip_Details_arr['eBookType'] = 'Later';
				}
				$trip_Details_arr['dTripDate'] = $book_time;
				$currencySymbol = $onecitydetail['vCurrencySymbol'];
				$ridelocationArr[] = 'Shuttle';
				$payment_type = $givenData['payment_type']; // Cash / Credit Card
				$trip_Details_arr['eTripLocation'] = $fixridedetail['eFaretype'];
				$trip_Details_arr['eDistanceUnit'] = $onecitydetail['eRadiusUnit'];
				if($payment_type=='Cash'){
					$trip_Details_arr['ePaymentType'] = 'Cash';
					$trip_Details_arr['iCustomerCreditCardId'] = 0;
				}else {
					$trip_Details_arr['ePaymentType'] = 'Credit Card';
					$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
				}
				$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
				$trip_Details_arr['tDestinationAddressLatLong'] = $finishlatlong;
				$trip_Details_arr['tRouteImgURL'] = "";
				$trip_Details_arr['iRideId'] = $givenData['ride_id'];
				$trip_Details_arr['eType'] = 'Fixride';
				$trip_Details_arr['eTripType'] = $fixridedetail['eTripType'];
				// code for newsletter promo code discount
				$newsletter_promo_code = '';
				if($givenData['newsletter_promo_code']){
					$newsletter_promo_code = $givenData['newsletter_promo_code'];
					// delete promocode from final table
					$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
					$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
					// end of code for delete promocode from final table
					$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);
					$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
					if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
						$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
						if($promo_data){
							$newsletter_promo_code = $givenData['newsletter_promo_code'];
							$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
						}else {
							$newsletter_promo_code = '';    
							$trip_Details_arr['iPromotionCodeId'] = '';
						}
					}else {
						$newsletter_promo_code = '';    
						$trip_Details_arr['iPromotionCodeId'] = '';
					}
				}

				// code for invitation code discounts
				$invitation_total_final_dis = 0;
				$invitecnt = 0;
				$invitemainarr = array();
				$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
				$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
				if($invitesinglecode){
					$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
					foreach ($invitefollowers as $key => $value) {
						$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
						if(empty($invitefollowersdata)){
							$invitearr = array();
							$invitearr['iClientId'] = $givenData['rider_id']; 
							$invitearr['iPromocodeUserId'] = $value['iClientId']; 
							$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
							$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
							array_push($invitemainarr, $iInvitationPromocodeId);
							$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
							$invitecnt++;
						}
					}
					if($invitecnt==0){
						$invitation_total_final_dis = 0;
					}   
				}
				// end of code for invitation code discounts
				// Fare Calculations
				$finalfare+=$givenData['total_adults']*$fixridedetail['fPreAdultFare'];
				$finalfare+=$givenData['total_child']*$fixridedetail['fPerChildFare'];
				$finalfare+=$givenData['total_infant']*$fixridedetail['fPreInfrantFare'];
				$finalfare+=$givenData['total_pets']*$fixridedetail['fPerPetFare'];
				
				if ($givenData['total_bags']>2) {
					$finalfare+=($givenData['total_bags']-2)*$fixridedetail['fPerBagFare'];
				}
				if ($givenData['total_hand_bags']>1) {
					$finalfare+=($givenData['total_hand_bags']-1)*$fixridedetail['fPerBagFare'];
				}
				$trip_Details_arr['fPerMileFare'] = '';
				$trip_Details_arr['fPerMinFare'] = '';
				$trip_Details_arr['fBaseFare'] = '';
				$trip_Details_arr['MinimumFare'] = $finalfare;
				//
				$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
				$trip_Details_arr['fServiceTax'] = $fServiceTax;
				$finalfare = $finalfare + $fServiceTax;
				$trip_Details_arr['dSubtotalPayment'] = $finalfare;
				$trip_Details_arr['fTotalMinute'] = $fixridedetail['fDuration'];
				$trip_Details_arr['fDistance'] = $fixridedetail['fDistance'];
				//*************** Discount calculations **********************
					$finaltotalinvitationbookdiscount = 0;
					if($invitation_total_final_dis){
						$finalpay = $finalfare;
						$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
						$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
						$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
					}

					$finaltotalpromobookdiscount = 0;
					if($newsletter_promo_code){
						$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);

						if($promo_data['eDiscountType']=='Percentage'){
							$percentage_promo = $promo_data['fDiscount'];
							$finalpay = $finalfare;
							$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
							$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
						}else if($promo_data['eDiscountType']=='Amount'){
							$finaltotalpromobookdiscount = $promo_data['fDiscount'];
							$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
						}
					}

					$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
					if($finalinvitebookdiscount>$finalfare){
						$trip_Details_arr['fFinalPayment'] = '0.00';
					}else {
						$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
					}
				//*************** Discount calculations **********************
				$trip_Details_arr['iClientId'] = $givenData['rider_id'];
				$trip_Details_arr['vPickupLocation'] = $fixridedetail['vPickUpLocation'];
				$trip_Details_arr['vDestinationLocation'] = $fixridedetail['vDropLocation'];
				$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
				$trip_Details_arr['ePaymentStatus'] = 'In progress';
				$trip_Details_arr['eStatus'] = 'Pending';
				$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
				$trip_Details_arr['eDriverAssign'] = 'No';
				$trip_Details_arr['eOutstationNotification'] = 'No';
				// ********************** save trip details arr **********************
				
				$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
				if($iTripId>0){
					if(count($invitemainarr)>0){
						$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
					}
							
					// send notification to rider
					$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
					if($rider_device_details){
						$pushNotificationData['action']  = 'sendNotification';
						$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
						$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
						$pushNotificationData['eUserType'] = "Rider";
						$datariderpush = $this->pushNotification($pushNotificationData);
					}
					// end of code send notification to rider
					// code for send notification to driver / VO
					$circleType='FixRide';
					$tripData['iTripId']=$iTripId;
					$tripData['lat_long']=$source_city_lat_long;
					$tripData['source_radius']=$source_city_radius;
					$tripData['qurVar']=$qurVar;
					$tripData['ridelocationArr']=$ridelocationArr;
					$tripData['car_id']=$givenData['car_id'];
					$tripData['book_time']=$book_time;
					$tripData['iCityId']=$trip_Details_arr['iCityId'];
					if ($booktype=='now'){
						$this->notifyDriverOnBooking($circleType,$tripData);
					}else{
						$tripData['source_address']=$fixridedetail['vPickUpLocation'];
						$tripData['destination_address']=$fixridedetail['vDropLocation'];
						$this->notifyOwnerOnBooking($circleType,$tripData);
					}
					$data['msg'] = 'Success';
				}else {
					$data['msg'] = 'Failure';
				}
			}else{
				if (!empty($fixridedetail)){
					$fixfaredetail = $this->webservices_model->get_fix_ride_fare_details($givenData['ride_id'],$givenData['car_id']);
					if($fixfaredetail==0){
						$data['msg'] = 'Failure';
						echo json_encode($data);exit;
					}
					$startlatlong = $this->GetLatLongFromAddress($fixridedetail['vPickUpLocation']);
					$finishlatlong = $this->GetLatLongFromAddress($fixridedetail['vDropLocation']);
					$onecitydetail = $this->webservices_model->get_one_city_details_byid($fixridedetail['iCityId']);
					$distanceUnit = $onecitydetail['eRadiusUnit'];
					$qurVar = ($distanceUnit=='KMs') ? 6371 : 3959;
					$source_city_lat_long = explode('|', $onecitydetail['tCityLatLong']);
					$source_city_radius =$onecitydetail['fRadius'];
					$payment_type = $givenData['payment_type']; // Cash / Credit Card
					$booktype=$givenData['booktype'];
					$trip_Details_arr = array();
					
					$tmpDate = new DateTime("now", new DateTimeZone($onecitydetail['vTimeZone']));
					$trip_Details_arr['dBookingDate'] = $tmpDate->format('Y-m-d H:i:s');
					if ($booktype=='now') {
						$book_time = $trip_Details_arr['dBookingDate'];
						$trip_Details_arr['eBookType'] = 'Now';
					} else {
						$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
						$trip_Details_arr['eBookType'] = 'Later';
					}
					$trip_Details_arr['dTripDate'] = $book_time;
					$currencySymbol = $onecitydetail['vCurrencySymbol'];
					if ($fixridedetail['eFaretype']=='Local') {
						$ridelocationArr = array('Local','Both');
					} else {
						$ridelocationArr = array('Outstation','Both');
					}
					$payment_type = $givenData['payment_type']; // Cash / Credit Card
					$trip_Details_arr['eTripLocation'] = $fixridedetail['eFaretype'];
					$trip_Details_arr['eDistanceUnit'] = $onecitydetail['eRadiusUnit'];
					if($payment_type=='Cash'){
						$trip_Details_arr['ePaymentType'] = 'Cash';
						$trip_Details_arr['iCustomerCreditCardId'] = 0;
					}else {
						$trip_Details_arr['ePaymentType'] = 'Credit Card';
						$trip_Details_arr['iCustomerCreditCardId'] = $givenData['customer_credit_card_id'];
					}
					$trip_Details_arr['tPickUpAddressLatLong'] = $startlatlong;
					$trip_Details_arr['tDestinationAddressLatLong'] = $finishlatlong;
					$trip_Details_arr['tRouteImgURL'] = "";
					$trip_Details_arr['iRideId'] = $givenData['ride_id'];
					$trip_Details_arr['eType'] = 'Fixride';
					$trip_Details_arr['eTripType'] = $fixridedetail['eTripType'];
					// code for newsletter promo code discount
					$newsletter_promo_code = '';
					if($givenData['newsletter_promo_code']){
						$newsletter_promo_code = $givenData['newsletter_promo_code'];
						// delete promocode from final table
						$promoinfo = $this->webservices_model->get_promocode_info($newsletter_promo_code);
						$totalrows = $this->webservices_model->delete_promocode_after_usage($givenData['rider_id'],$promoinfo['iPrmotionCodeId']);
						// end of code for delete promocode from final table
						$newletter_code_limit = $this->webservices_model->valid_newsletter_code_limit($newsletter_promo_code);
						$total_newsletter_promo_usage_cnt = $this->webservices_model->get_newsletter_promocode_usage_count($newsletter_promo_code);
						if($total_newsletter_promo_usage_cnt<$newletter_code_limit){
							$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);
							if($promo_data){
								$newsletter_promo_code = $givenData['newsletter_promo_code'];
								$trip_Details_arr['iPromotionCodeId'] = $promo_data['iPrmotionCodeId'];
							}else {
								$newsletter_promo_code = '';    
								$trip_Details_arr['iPromotionCodeId'] = '';
							}
						}else {
							$newsletter_promo_code = '';    
							$trip_Details_arr['iPromotionCodeId'] = '';
						}
					}

					// code for invitation code discounts
					$invitation_total_final_dis = 0;
					$invitecnt = 0;
					$invitemainarr = array();
					$invitationdisc = $this->webservices_model->get_invitation_fare_discount();
					$invitesinglecode = $this->webservices_model->get_client_invitation_single_code($givenData['rider_id']);
					if($invitesinglecode){
						$invitefollowers = $this->webservices_model->get_all_invitation_code_follwers($invitesinglecode);
						foreach ($invitefollowers as $key => $value) {
							$invitefollowersdata = $this->webservices_model->check_code_exist_or_not($givenData['rider_id'],$value['iClientId'],$value['vInvitePromotionCode']);
							if(empty($invitefollowersdata)){
								$invitearr = array();
								$invitearr['iClientId'] = $givenData['rider_id']; 
								$invitearr['iPromocodeUserId'] = $value['iClientId']; 
								$invitearr['vPromotionCode'] = $value['vInvitePromotionCode']; 
								$iInvitationPromocodeId = $this->webservices_model->save_invitation_codes($invitearr);
								array_push($invitemainarr, $iInvitationPromocodeId);
								$invitation_total_final_dis = ($invitation_total_final_dis + $invitationdisc);
								$invitecnt++;
							}
						}
						if($invitecnt==0){
							$invitation_total_final_dis = 0;
						}   
					}
					// end of code for invitation code discounts
					// Fare Calculations
					$finalfare = $fixfaredetail;
					$trip_Details_arr['fPerMileFare'] = '';
					$trip_Details_arr['fPerMinFare'] = '';
					$trip_Details_arr['fBaseFare'] = '';
					$trip_Details_arr['MinimumFare'] = $fixfaredetail;
					//
					$fServiceTax = (($finalfare * $onecitydetail['fServicetax'])/100);
					$trip_Details_arr['fServiceTax'] = $fServiceTax;
					$finalfare = $finalfare + $fServiceTax;

					$trip_Details_arr['dSubtotalPayment'] = $finalfare;
					$trip_Details_arr['fTotalMinute'] = $fixridedetail['fDuration'];
					$trip_Details_arr['fDistance'] = $fixridedetail['fDistance'];
					//*************** Discount calculations **********************
						$finaltotalinvitationbookdiscount = 0;
						if($invitation_total_final_dis){
							$finalpay = $finalfare;
							$finaltotalinvitationbookdiscount = (($finalpay*$invitation_total_final_dis)/100);
							$trip_Details_arr['iInvitationCodeTotalDiscount'] = $invitation_total_final_dis;
							$trip_Details_arr['dInvitePromoCodeDiscount'] = $finaltotalinvitationbookdiscount;
						}

						$finaltotalpromobookdiscount = 0;
						if($newsletter_promo_code){
							$promo_data = $this->webservices_model->check_newsletter_promo_code_status($newsletter_promo_code);

							if($promo_data['eDiscountType']=='Percentage'){
								$percentage_promo = $promo_data['fDiscount'];
								$finalpay = $finalfare;
								$finaltotalpromobookdiscount = (($finalpay*$percentage_promo)/100);
								$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
							}else if($promo_data['eDiscountType']=='Amount'){
								$finaltotalpromobookdiscount = $promo_data['fDiscount'];
								$trip_Details_arr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
							}
						}

						$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
						if($finalinvitebookdiscount>$finalfare){
							$trip_Details_arr['fFinalPayment'] = '0.00';
						}else {
							$trip_Details_arr['fFinalPayment'] = ($finalfare - $finalinvitebookdiscount);
						}
					//*************** Discount calculations **********************
					$trip_Details_arr['iClientId'] = $givenData['rider_id'];
					$trip_Details_arr['vPickupLocation'] = $fixridedetail['vPickUpLocation'];
					$trip_Details_arr['vDestinationLocation'] = $fixridedetail['vDropLocation'];
					$trip_Details_arr['iVehicleCompanyId'] = $givenData['car_id'];
					$trip_Details_arr['ePaymentStatus'] = 'In progress';
					$trip_Details_arr['eStatus'] = 'Pending';
					$trip_Details_arr['iCityId'] = $onecitydetail['iCityId'];
					$trip_Details_arr['eDriverAssign'] = 'No';
					$trip_Details_arr['eOutstationNotification'] = 'No';
					// ********************** save trip details arr **********************
					
					$iTripId = $this->webservices_model->add_trip_details($trip_Details_arr);
					if($iTripId>0){
						if(count($invitemainarr)>0){
							$inv_id = $this->webservices_model->update_invitation_code_with_promo($invitemainarr,$iTripId);  
						}
								
						// send notification to rider
						$rider_device_details = $this->webservices_model->get_rider_device_details($_REQUEST['rider_id']);
						if($rider_device_details){
							$pushNotificationData['action']  = 'sendNotification';
							$pushNotificationData['msg'] = "Thank you for placing a request for pickup at OneTouchCab!";
							$pushNotificationData['vDeviceid'] = $rider_device_details['device_id'];
							$pushNotificationData['eUserType'] = "Rider";
							$datariderpush = $this->pushNotification($pushNotificationData);
						}
						// end of code send notification to rider
						// code for send notification to driver / VO
						$circleType='FixRide';
						$tripData['iTripId']=$iTripId;
						$tripData['lat_long']=$source_city_lat_long;
						$tripData['source_radius']=$source_city_radius;
						$tripData['qurVar']=$qurVar;
						$tripData['ridelocationArr']=$ridelocationArr;
						$tripData['car_id']=$givenData['car_id'];
						$tripData['book_time']=$book_time;
						$tripData['iCityId']=$trip_Details_arr['iCityId'];
						if ($booktype=='now'){
							$this->notifyDriverOnBooking($circleType,$tripData);
						}else{
							$tripData['source_address']=$fixridedetail['vPickUpLocation'];
							$tripData['destination_address']=$fixridedetail['vDropLocation'];
							$this->notifyOwnerOnBooking($circleType,$tripData);
						}
						$data['msg'] = 'Success';
					}else {
						$data['msg'] = 'Failure';
					}
				}else{
					$data['msg'] = "Failure";
				}
			}	
		} else {
			$data['msg'] = 'Failure';
		}
		// Send Responce
		echo json_encode($data);exit;
	}

	function notifyDriverOnBooking($circleType,$tripData){
		if($circleType=='Taxi'){
			// $this->printthisexitexit($tripData);
			if ($tripData['ridelocation']=='Local'){
				$update_res = $this->webservices_model->update_trip_details($tripData['iTripId'],array('eOutstationNotification'=>'No'));
				$drivers = $this->webservices_model->get_all_drivers_within_city($tripData['lat_long'][0],$tripData['lat_long'][1],$tripData['source_radius'],$tripData['qurVar'],$tripData['ridelocationArr'],$tripData['car_id']);
			} else {
				$min_limit = date('Y-m-d H:i', strtotime($tripData['book_time'].'-2 hours'));
				$max_limit = date('Y-m-d H:i', strtotime($tripData['book_time'].'+2 hours'));
				$all_out_stations=$this->webservices_model->getAllOutOfStationNew($min_limit,$max_limit,$tripData['source'],$tripData['destination']);
				if (count($all_out_stations)>0) {
					foreach ($all_out_stations as $oskey => $osvalue) {
						$drivers[$oskey]['iDriverId'] = $osvalue['iDriverId'];
						$drivers[$oskey]['iVehicleOwnerId'] = $osvalue['iVehicleOwnerId'];
					}
					$update_res = $this->webservices_model->update_trip_details($tripData['iTripId'],array('eOutstationNotification'=>'Yes'));
				}else{
					$update_res = $this->webservices_model->update_trip_details($tripData['iTripId'],array('eOutstationNotification'=>'No'));
					$drivers = $this->webservices_model->get_all_drivers_within_city($tripData['lat_long'][0],$tripData['lat_long'][1],$tripData['source_radius'],$tripData['qurVar'],$tripData['ridelocationArr'],$tripData['car_id']);	
				}
			}
			if(count($drivers)>0){
				foreach ($drivers as $adkey => $advalue) {
					$mainarr['iDriverId'] = $advalue['iDriverId'];
					$mainarr['iVehicleOwnerId'] = $advalue['iVehicleOwnerId'];
					$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($mainarr['iDriverId']);
					if($countRunningTrip==0){
						// $this->printthisexit($drivers);
						$mainarr['iTripId'] = $tripData['iTripId'];
						$status = $this->webservices_model->check_trip_and_driver($mainarr);
						if($status=='not exist'){
							$this->webservices_model->add_trip_and_driver($mainarr);
						}

						// code for sending push notification
						$driver_device_details = $this->webservices_model->get_driver_device_details($mainarr['iDriverId']);
						if($driver_device_details){
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$tripData['iTripId'];
							$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
							$pushNotificationData['eUserType'] = "Driver";
							$datapush = $this->pushNotification($pushNotificationData);
						}
					} // end of countRunningTrip if
				} // end of foreach
			} // end of models if 
			// $this->printthisexit("END");
		}else if($circleType=='CarPool'){
			// $this->printthis('tripData');
			// $this->printthis($tripData);
			
			//------------------------------------
			if($tripData['eSmokingPreference']=='Yes'){
				$smokingPref= array('Yes','Does Not Matter');
			}else if($tripData['eSmokingPreference']=='No'){
				$smokingPref= array('No');
			}else{
				$smokingPref= array('Yes','No','Does Not Matter');
			}

			if($tripData['eGenderPreference']=='Male'){
				$genderPref= array('Male','Does Not Matter');
			}else if($tripData['eGenderPreference']=='Female'){
				$genderPref= array('Female','Does Not Matter');
			}else{
				$genderPref= array('Male','Female','Does Not Matter');
			}
			$pickLatLong = explode('|', $tripData['pickLatLong']);
			$destLatLong = explode('|', $tripData['destLatLong']);

			# check with sLat, sLong with source address, and dest too
			$driversbySource = $this->webservices_model->getAllPoolDriversWithinCity($pickLatLong[0],$pickLatLong[1],$tripData['source_radius'], $tripData['qurVarSource'],$tripData['ridelocationArr'],$tripData['car_id'],$smokingPref,$genderPref);
			// $this->printthis("driversbySource");
			// $this->printthis($driversbySource);
			$iCarpoolIds= array();
			foreach ($driversbySource as $dskey => $drSource){
				$iCarpoolIds[]=$drSource['iCarpoolId'];
			}
			
			$driversbyDest = $this->webservices_model->getAllPoolDriversBySource($destLatLong[0],$destLatLong[1],$tripData['dest_radius'], $tripData['qurVardest'],$tripData['ridelocationArr'],$tripData['car_id'],$smokingPref,$genderPref,$iCarpoolIds);
			// $this->printthis("driversbyDest");
			// $this->printthis($driversbyDest);
			
			// get unique driver ids

			$driverArr= array();
			$driverTmpArr= array();
			if (count($driversbyDest) > 0) {
				foreach ($driversbyDest as $dskey => $drDest){
					if (!in_array($drDest['iDriverId'], $driverArr)) {
						$countRunningTrip = $this->webservices_model->checkRunningTripByDriver($drDest['iDriverId']);
						if($countRunningTrip==0){
							$driverArr[]=$drDest['iDriverId'];

							$mainarr['iDriverId'] = $drDest['iDriverId'];
							$mainarr['iVehicleOwnerId'] = $drDest['iVehicleOwnerId'];
							$mainarr['iTripId'] = $tripData['iTripId'];
							// $this->printthis("Send to Driver : ");
							// $this->printthis($drDest['iDriverId']);
							$this->webservices_model->add_trip_and_driver($mainarr);
							
							// code for sending push notification
							$driver_device_details = $this->webservices_model->get_driver_device_details($mainarr['iDriverId']);
							if($driver_device_details){
								$pushNotificationData['action'] = 'sendNotification';
								$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$tripData['iTripId'];
								$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
								$pushNotificationData['eUserType'] = "Driver";
								$datapush = $this->pushNotification($pushNotificationData);
							}
							
						}
					}
				}
			}
		}else if($circleType=='LocalByDuration'){
			// $this->printthisexitexit($tripData);
			$drivers = $this->webservices_model->get_all_drivers_within_city($tripData['lat_long'][0],$tripData['lat_long'][1],$tripData['source_radius'],$tripData['qurVar'],$tripData['ridelocationArr'],$tripData['car_id']);
			if(count($drivers)>0){
				foreach ($drivers as $adkey => $advalue) {
					$mainarr['iDriverId'] = $advalue['iDriverId'];
					$mainarr['iVehicleOwnerId'] = $advalue['iVehicleOwnerId'];
					$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($mainarr['iDriverId']);
					if($countRunningTrip==0){
						// $this->printthisexit($drivers);
						$mainarr['iTripId'] = $tripData['iTripId'];
						$status = $this->webservices_model->check_trip_and_driver($mainarr);
						if($status=='not exist'){
							$this->webservices_model->add_trip_and_driver($mainarr);
						}

						// code for sending push notification
						$driver_device_details = $this->webservices_model->get_driver_device_details($mainarr['iDriverId']);
						if($driver_device_details){
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$tripData['iTripId'];
							$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
							$pushNotificationData['eUserType'] = "Driver";
							$datapush = $this->pushNotification($pushNotificationData);
						}
					} // end of countRunningTrip if
				} // end of foreach
			} // end of drivers if 
		}else if($circleType=='OutStationByDuration'){
			$min_limit = date('Y-m-d H:i', strtotime($tripData['book_time'].'-2 hours'));
			$max_limit = date('Y-m-d H:i', strtotime($tripData['book_time'].'+2 hours'));
			$all_out_stations=$this->webservices_model->getAllOutOfStationNew($min_limit,$max_limit,$tripData['source'],$tripData['destination']);
			if (count($all_out_stations)>0) {
				foreach ($all_out_stations as $oskey => $osvalue) {
					$drivers[$oskey]['iDriverId'] = $osvalue['iDriverId'];
					$drivers[$oskey]['iVehicleOwnerId'] = $osvalue['iVehicleOwnerId'];
				}
				$update_res = $this->webservices_model->update_trip_details($tripData['iTripId'],array('eOutstationNotification'=>'Yes'));
			}else{
				$update_res = $this->webservices_model->update_trip_details($tripData['iTripId'],array('eOutstationNotification'=>'No'));
				$drivers = $this->webservices_model->get_all_drivers_within_city($tripData['lat_long'][0],$tripData['lat_long'][1],$tripData['source_radius'],$tripData['qurVar'],$tripData['ridelocationArr'],$tripData['car_id']);	
			}
			if(count($drivers)>0){
				foreach ($drivers as $adkey => $advalue) {
					$mainarr['iDriverId'] = $advalue['iDriverId'];
					$mainarr['iVehicleOwnerId'] = $advalue['iVehicleOwnerId'];
					$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($mainarr['iDriverId']);
					if($countRunningTrip==0){
						// $this->printthisexit($drivers);
						$mainarr['iTripId'] = $tripData['iTripId'];
						$status = $this->webservices_model->check_trip_and_driver($mainarr);
						if($status=='not exist'){
							$this->webservices_model->add_trip_and_driver($mainarr);
						}

						// code for sending push notification
						$driver_device_details = $this->webservices_model->get_driver_device_details($mainarr['iDriverId']);
						if($driver_device_details){
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$tripData['iTripId'];
							$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
							$pushNotificationData['eUserType'] = "Driver";
							$datapush = $this->pushNotification($pushNotificationData);
						}
					} // end of countRunningTrip if
				} // end of foreach
			}
		}else if($circleType=='Shuttle'){
			$drivers = $this->webservices_model->get_all_drivers_within_city($tripData['lat_long'][0],$tripData['lat_long'][1],$tripData['source_radius'],$tripData['qurVar'],$tripData['ridelocationArr'],$tripData['car_id']);
			if(count($drivers)>0){
				foreach ($drivers as $adkey => $advalue) {
					$mainarr['iDriverId'] = $advalue['iDriverId'];
					$mainarr['iVehicleOwnerId'] = $advalue['iVehicleOwnerId'];
					$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($mainarr['iDriverId']);
					if($countRunningTrip==0){
						// $this->printthisexit($drivers);
						$mainarr['iTripId'] = $tripData['iTripId'];
						$status = $this->webservices_model->check_trip_and_driver($mainarr);
						if($status=='not exist'){
							$this->webservices_model->add_trip_and_driver($mainarr);
						}

						// code for sending push notification
						$driver_device_details = $this->webservices_model->get_driver_device_details($mainarr['iDriverId']);
						if($driver_device_details){
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$tripData['iTripId'];
							$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
							$pushNotificationData['eUserType'] = "Driver";
							$datapush = $this->pushNotification($pushNotificationData);
						}
					} // end of countRunningTrip if
				} // end of foreach
			}
		}else if($circleType=='FixRide'){
			$drivers = $this->webservices_model->get_all_drivers_within_city($tripData['lat_long'][0],$tripData['lat_long'][1],$tripData['source_radius'],$tripData['qurVar'],$tripData['ridelocationArr'],$tripData['car_id']);
			if(count($drivers)>0){
				foreach ($drivers as $adkey => $advalue) {
					$mainarr['iDriverId'] = $advalue['iDriverId'];
					$mainarr['iVehicleOwnerId'] = $advalue['iVehicleOwnerId'];
					$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($mainarr['iDriverId']);
					if($countRunningTrip==0){
						// $this->printthisexit($drivers);
						$mainarr['iTripId'] = $tripData['iTripId'];
						$status = $this->webservices_model->check_trip_and_driver($mainarr);
						if($status=='not exist'){
							$this->webservices_model->add_trip_and_driver($mainarr);
						}

						// code for sending push notification
						$driver_device_details = $this->webservices_model->get_driver_device_details($mainarr['iDriverId']);
						if($driver_device_details){
							$pushNotificationData['action'] = 'sendNotification';
							$pushNotificationData['msg'] = "OneTocuhCab found a new pickup request for your service! ||||| ".$tripData['iTripId'];
							$pushNotificationData['vDeviceid'] = $driver_device_details['device_id'];
							$pushNotificationData['eUserType'] = "Driver";
							$datapush = $this->pushNotification($pushNotificationData);
						}
					} // end of countRunningTrip if
				} // end of foreach
			} // end of drivers if 
		}
	}

	function notifyOwnerOnBooking($circleType,$tripData){
		if($circleType=='Taxi'){
			// Get VO who have car type as book type
			$ownerIDs = $this->webservices_model->getOwnerForBookLater($tripData['car_id'],$tripData['iCityId']);
			// $this->printthisexit($ownerIDs);
			foreach ($ownerIDs as $okey => $ownerID) {
				$ownerDevice = $this->webservices_model->getOwnerDevice($ownerID['iVehicleOwnerId']);
				if($ownerDevice['device_id']){
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = "OneTouchCab found a new pickup request for your service for ".$tripData['book_time']." for ".$tripData['source_address']." -> ".$tripData['destination_address']." ||||| ".$tripData['iTripId'];
					$pushNotificationData['vDeviceid'] = $ownerDevice['device_id'];
					$pushNotificationData['eUserType'] = "Owner";
					$datapush = $this->pushNotification($pushNotificationData);
					// Save to trip_driver
					$newData['iTripId']=$tripData['iTripId'];
					$newData['iVehicleOwnerId']=$ownerID['iVehicleOwnerId'];
					$this->webservices_model->add_trip_and_driver($newData);
				}
			}
		}else if($circleType=='CarPool'){
			# code...
		}else if($circleType=='LocalByDuration'){
			// Get VO who have car type as book type
			$ownerIDs = $this->webservices_model->getOwnerForBookLater($tripData['car_id'],$tripData['iCityId']);
			// $this->printthisexit($ownerIDs);
			foreach ($ownerIDs as $okey => $ownerID) {
				$ownerDevice = $this->webservices_model->getOwnerDevice($ownerID['iVehicleOwnerId']);
				if($ownerDevice['device_id']){
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = "OneTouchCab found a new pickup request for your service for ".$tripData['book_time']." for ".$tripData['source_address']." -> ".$tripData['vRoundOption']." ||||| ".$tripData['iTripId'];
					$pushNotificationData['vDeviceid'] = $ownerDevice['device_id'];
					$pushNotificationData['eUserType'] = "Owner";
					$datapush = $this->pushNotification($pushNotificationData);
					// Save to trip_driver
					$newData['iTripId']=$tripData['iTripId'];
					$newData['iVehicleOwnerId']=$ownerID['iVehicleOwnerId'];
					$this->webservices_model->add_trip_and_driver($newData);
				}
			}
		}else if($circleType=='OutStationByDuration'){
			// Get VO who have car type as book type
			$ownerIDs = $this->webservices_model->getOwnerForBookLater($tripData['car_id'],$tripData['iCityId']);
			// $this->printthisexit($ownerIDs);
			foreach ($ownerIDs as $okey => $ownerID) {
				$ownerDevice = $this->webservices_model->getOwnerDevice($ownerID['iVehicleOwnerId']);
				if($ownerDevice['device_id']){
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = "OneTouchCab found a new pickup request for your service for ".$tripData['book_time']." for ".$tripData['source_address']." -> ".$tripData['destination_address']." ||||| ".$tripData['iTripId'];
					$pushNotificationData['vDeviceid'] = $ownerDevice['device_id'];
					$pushNotificationData['eUserType'] = "Owner";
					$datapush = $this->pushNotification($pushNotificationData);
					// Save to trip_driver
					$newData['iTripId']=$tripData['iTripId'];
					$newData['iVehicleOwnerId']=$ownerID['iVehicleOwnerId'];
					$this->webservices_model->add_trip_and_driver($newData);
				}
			}
		}else if($circleType=='Shuttle'){
			$ownerIDs = $this->webservices_model->getOwnerForBookLater($tripData['car_id'],$tripData['iCityId']);
			// $this->printthisexit($ownerIDs);
			foreach ($ownerIDs as $okey => $ownerID) {
				$ownerDevice = $this->webservices_model->getOwnerDevice($ownerID['iVehicleOwnerId']);
				if($ownerDevice['device_id']){
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = "OneTouchCab found a new pickup request for your service for ".$tripData['book_time']." for ".$tripData['source_address']." -> ".$tripData['destination_address']." ||||| ".$tripData['iTripId'];
					$pushNotificationData['vDeviceid'] = $ownerDevice['device_id'];
					$pushNotificationData['eUserType'] = "Owner";
					$datapush = $this->pushNotification($pushNotificationData);
					// Save to trip_driver
					$newData['iTripId']=$tripData['iTripId'];
					$newData['iVehicleOwnerId']=$ownerID['iVehicleOwnerId'];
					$this->webservices_model->add_trip_and_driver($newData);
				}
			}
		}else if($circleType=='FixRide'){
			$ownerIDs = $this->webservices_model->getOwnerForBookLater($tripData['car_id'],$tripData['iCityId']);
			// $this->printthisexit($ownerIDs);
			foreach ($ownerIDs as $okey => $ownerID) {
				$ownerDevice = $this->webservices_model->getOwnerDevice($ownerID['iVehicleOwnerId']);
				if($ownerDevice['device_id']){
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = "OneTouchCab found a new pickup request for your service for ".$tripData['book_time']." for ".$tripData['source_address']." -> ".$tripData['destination_address']." ||||| ".$tripData['iTripId'];
					$pushNotificationData['vDeviceid'] = $ownerDevice['device_id'];
					$pushNotificationData['eUserType'] = "Owner";
					$datapush = $this->pushNotification($pushNotificationData);
					// Save to trip_driver
					$newData['iTripId']=$tripData['iTripId'];
					$newData['iVehicleOwnerId']=$ownerID['iVehicleOwnerId'];
					$this->webservices_model->add_trip_and_driver($newData);
				}
			}
		}
	}

	function ConfirmTripByOwner(){
		if($this->input->post('iTripId') && $this->input->post('iVehicleOwnerId')){
			$iTripId = $this->input->post('iTripId');
			$iVehicleOwnerId = $this->input->post('iVehicleOwnerId');
			$trip_exist = $this->webservices_model->check_trip_exists($iTripId);
			$owner_status = $this->webservices_model->check_owner_exists($iVehicleOwnerId);
			if($trip_exist=='exist'){
				if($owner_status=='exist'){
					$trip_status = $this->webservices_model->checkOwnerTripStatus($iVehicleOwnerId, $iTripId);
					if ($trip_status=='exist') {
						$trip_detail = $this->webservices_model->gettrip($iTripId);
						// condition for time
						$trip_city_detail = $this->webservices_model->get_one_city_details_byid($trip_detail['iCityId']);

						/*$datetime2 = new DateTime($trip_detail['dBookingDate']);
						$datetime1 = new DateTime("now", new DateTimeZone($trip_city_detail['vTimeZone']));
						$diff = $datetime1->diff($datetime2);*/
						//-------------------------
						$bookTime = new DateTime($trip_detail['dBookingDate']);
						$driverTime = new DateTime('now', new DateTimeZone($trip_city_detail['vTimeZone']));
						$acceptTime = $driverTime->format('Y-m-d H:i:s');
						$compare = new DateTime($acceptTime);
						$diff = $compare->diff($bookTime);
						//-------------------------
						/*$this->printthis("Diff");
						$this->printthisexit($diff->i);*/
						if ($diff->i < 3 ) {
							if ($trip_detail['eStatus']=='Pending') {
								$tripdataarr['iVehicleOwnerId']=$iVehicleOwnerId;
								$tripdataarr['eStatus']='Confirmed';
								$this->webservices_model->update_trip_details($iTripId, $tripdataarr);
								// Remove other VO
								$this->webservices_model->deleteOtherTripOwners($iVehicleOwnerId, $iTripId);
								// Send Notification to Customer
								// Your pickup request for <later date> <later time> for <source -> destination> is CONFIRMED. Very soon we will inform you about your assigned driver for your trip.
								$rider_details = $this->webservices_model->get_rider_device_details_by_id($trip_detail['iClientId']);
								// code for sending push notification
								if($rider_details){
									$pushNotificationData['action'] = 'sendNotification';
									$pushNotificationData['msg'] = "Your pickup request for ".$trip_detail['dTripDate']." for ".$trip_detail['vPickupLocation']." -> ".$trip_detail['vPickupLocation']." is CONFIRMED. Very soon we will inform you about your assigned driver for your trip ? ".$iTripId;
									$pushNotificationData['vDeviceid'] = $rider_details['device_id'];
									$pushNotificationData['eUserType'] = "Rider";
									$datapush = $this->pushNotification($pushNotificationData);
								}
								$data['msg'] = "Success";
							}else {
								$data['msg'] = "Sorry, This Trip is already accepted by another vehicle owner";
							}
						}else{
							$this->webservices_model->reject_trip_by_owner($iVehicleOwnerId, $iTripId);
							$data['msg'] = 'The trip is no longer available';
						}
					}else{
						$data['msg'] = "Sorry, This Trip is already accepted by another vehicle owner";
					}
				}else {
					$data['msg'] = 'Vehicle Owner Not Exist';  
				}
			}else {
				$data['msg'] = 'Trip Not Exist';
			}
		}else {
			$data['msg'] = 'Failure';
		}
		// Send Responce
		echo json_encode($data);exit;
	}

	function RejectTripByOwner(){
		if($_REQUEST['iVehicleOwnerId'] && $_REQUEST['iTripId']){
			$iVehicleOwnerId = $_REQUEST['iVehicleOwnerId'];
			$iTripId = $_REQUEST['iTripId'];
			$trip_detail = $this->webservices_model->gettrip($iTripId);
			if ($trip_detail) {
				$trip_status = $this->webservices_model->checkOwnerTripStatus($iVehicleOwnerId, $iTripId);
				if ($trip_status=='exist') {
					$this->webservices_model->reject_trip_by_owner($iVehicleOwnerId, $iTripId);
					// check no. of owners remain
					$noofOwners = $this->webservices_model->get_no_of_driver_assign_to_trip($iTripId);                    
					if($noofOwners==0){
						// Cancel the trip
						$tripdataarr['eStatus']='Cancel';
						$this->webservices_model->update_trip_details($iTripId, $tripdataarr);
						// send notification to customer
						$device_no = $this->webservices_model->get_client_device_no($iTripId);                    
						$pushNotificationData['action'] = 'sendNotification';
						$pushNotificationData['msg'] = "No Service Available at this Time";
						$pushNotificationData['vDeviceid'] = $device_no;
						$pushNotificationData['eUserType'] = "Rider";
						$datapush = $this->pushNotification($pushNotificationData);
					}
					$data['msg'] = "Success";
				}else{
					$data['msg'] = "Success";
				}
			}else{
				$data['msg'] = 'Trip Not Exist';
			}
		}else{
			$data['msg'] = "Failure";
		}
		echo json_encode($data);
		exit;
	}

	function AllocateDriverToTrip(){
		if($this->input->post('iTripId') && $this->input->post('iDriverId')){
			$iDriverId = $this->input->post('iDriverId');
			$iTripId = $this->input->post('iTripId');
			$tripData = $this->webservices_model->gettrip($iTripId);
			
			if ($tripData) {
				if ($tripData['eStatus']=='Cancel') {
					$data['msg'] = 'Trip is Cancelled';
					echo json_encode($data);exit;
				}
				
				$tripdataarr['eDriverAssign']='Yes';
				$this->webservices_model->update_trip_details($iTripId, $tripdataarr);
				$this->webservices_model->updateTripDriver($iTripId,array('iDriverId'=>$iDriverId));

				$driverDetail = $this->webservices_model->get_driver_details($iDriverId);
				// Notify Customer
				// <driver name> is assigned for  <later date> <later time> for <source -> destination>.
				$device_no = $this->webservices_model->get_client_device_no($iTripId);
				$pushDataClient['action'] = 'sendNotification';
				$pushDataClient['msg'] = "Driver ".$driverDetail['vFirstName']." ".$driverDetail['vLastName']." is assigned for ".$tripData['dTripDate']." for ".$tripData['vPickupLocation']." ".$tripData['vDestinationLocation'];
				$pushDataClient['vDeviceid'] = $device_no;
				$pushDataClient['eUserType'] = "Rider";
				$datapush = $this->pushNotification($pushDataClient);
				// Notify Driver
				// You have been assigned for a pickup for <later date> <later time> for <source -> destination>
				$driver_device_details = $this->webservices_model->get_driver_device_details($iDriverId);
				$pushDataDriver['action'] = 'sendNotification';
				$pushDataDriver['msg'] = "You have been assigned for a pickup for ".$tripData['dTripDate']." for ".$tripData['vPickupLocation']." ".$tripData['vDestinationLocation']."  |||||| ".$tripData['iTripId'];
				$pushDataDriver['vDeviceid'] = $driver_device_details['device_id'];
				$pushDataDriver['eUserType'] = "Driver";
				$datapush = $this->pushNotification($pushDataDriver);
				$data['msg'] = 'Success';
			} else {
				$data['msg'] = 'Trip is Cancelled';
			}
		}else{
			$data['msg'] = 'Failure';
		}
		echo json_encode($data);
		exit;
	}

	function UnAllocateDriverToTrip(){
		if($this->input->post('iTripId') && $this->input->post('iDriverId')){
			$iDriverId = $this->input->post('iDriverId');
			$iTripId = $this->input->post('iTripId');
			$tripData = $this->webservices_model->gettrip($iTripId);
			if ($tripData) {
				if ($tripData['eStatus']=='Cancel') {
					$data['msg'] = 'Trip is Cancelled';
					echo json_encode($data);exit;
				}
				$tripdataarr['eDriverAssign']='No';
				$this->webservices_model->update_trip_details($iTripId, $tripdataarr);
				$this->webservices_model->updateTripDriver($iTripId,array('iDriverId'=>0));

				$device_no = $this->webservices_model->get_client_device_no($iTripId);
				// Your pickup request for <later date> <later time> for <source - destination> is confirmed. We are still working on assignment of the driver for your trip
				$pushDataClient['action'] = 'sendNotification';
				$pushDataClient['msg'] = "Your pickup request for ".$tripData['dTripDate']." for ".$tripData['vPickupLocation']." ".$tripData['vDestinationLocation']." is confirmed. We are still working on assignment of the driver for your trip";
				$pushDataClient['vDeviceid'] = $device_no;
				$pushDataClient['eUserType'] = "Rider";
				$datapush = $this->pushNotification($pushDataClient);
				$data['msg'] = 'Success';
			} else {
				$data['msg'] = 'Trip is Cancelled';
			}
		}else{
			$data['msg'] = 'Failure';
		}
		echo json_encode($data);
		exit;
	}

	function getDriverListToAllocateTrip(){
		if($this->input->post('iTripId') && $this->input->post('iVehicleOwnerId')){
			// Check trip canceled
			$tripData = $this->webservices_model->gettrip($this->input->post('iTripId'));
			if ($tripData) {
				if ($tripData['eStatus']=='Cancel') {
					$data['msg'] = 'Trip is Cancelled';
					echo json_encode($data);exit;
				}
				// Get Drivers
				$drivers = $this->webservices_model->allDriversToAllocateTrip($this->input->post('iVehicleOwnerId'));
				/*$this->printthis("drivers");
				$this->printthis($drivers);*/
				$resArr = array();
				$driverIds=array();
				foreach($drivers as $key => $driver){
					$driverTripCount = $this->webservices_model->driverStatusForBookLater($driver['iDriverId']);
					if($driverTripCount==0 && !in_array($driver['iDriverId'], $driverIds)){
						$driverIds[]=$driver['iDriverId'];
						$driverVehicle = $this->webservices_model->getDriverVehicle($driver['iDriverId']);
						if ($driverVehicle) {
							$driver['vCompany']= $driverVehicle['vCompany'];
							$driver['vModelName']= $driverVehicle['vModelName'];
						} else {
							$driver['vCompany']= '';
							$driver['vModelName']= '';
						}
						$resArr[]=$driver;
					}
				}
				/*$this->printthis("resArr");
				$this->printthisexit($resArr);*/
				if(count($resArr)>0){
					$data['data'] = $resArr;
					$data['msg'] = 'Success';
				}else{
					$data['msg'] = 'No Driver Available For Assignment Now';
				}
			}else{
				$data['msg'] = 'Trip is Cancelled';
			}
		}else{
			$data['msg'] = 'Failure';
		}
		echo json_encode($data);
		exit;
	}

	function getClientTrips(){
		if($this->input->post('iClientId') && $this->input->post('type')){
			$iClientId=$this->input->post('iClientId');
			$type = $this->input->post('type');
			$filterType = ($type=='all') ? '' : $type ;
			$tripsDetail = $this->webservices_model->getTripDetailsForClient($iClientId,$filterType);
			$base_upload=$this->data['base_upload'];
			
			for($i=0;$i<count($tripsDetail);$i++){
				$trips_detail[$i]['iTripId'] = $tripsDetail[$i]['iTripId'];
				if($tripsDetail[$i]['eTripLocation']=='OutStationByDuration'){
					$trips_detail[$i]['fMinimumKm']=$this->webservices_model->getOutstationBydurationMinkmMiles($tripsDetail[$i]['iVehicleCompanyId'],$tripsDetail[$i]['iCityId']);
				}else{
					$trips_detail[$i]['fMinimumKm']='';
				}
				$driver_details = $this->webservices_model->get_trip_driver_details($trips_detail[$i]['iTripId']);  
				if(empty($driver_details)){
					$trips_detail[$i]['iDriverId'] = '';
					$trips_detail[$i]['vDriverFullname'] = '';
				}
				else {
					$trips_detail[$i]['iDriverId'] = $driver_details['iDriverId'];
					$trips_detail[$i]['vDriverFullname'] = $driver_details['vFirstName'].' '.$driver_details['vLastName'];
				}
				
				$trips_detail[$i]['iClientId'] = $tripsDetail[$i]['iClientId'];
				$trips_detail[$i]['vPickupLocation'] = $tripsDetail[$i]['vPickupLocation'];
				$trips_detail[$i]['vDestinationLocation'] = $tripsDetail[$i]['vDestinationLocation'];
				$trips_detail[$i]['eTripLocation'] = $tripsDetail[$i]['eTripLocation'];
				$trips_detail[$i]['eType'] = $tripsDetail[$i]['eType'];
				$trips_detail[$i]['eBookType'] = $tripsDetail[$i]['eBookType'];
				$trips_detail[$i]['eTripType'] = $tripsDetail[$i]['eTripType'];
				$trips_detail[$i]['vRoundOption'] = $tripsDetail[$i]['vRoundOption'];
				$trips_detail[$i]['fQuoteFare'] = $tripsDetail[$i]['vCurrencySymbol'].$tripsDetail[$i]['fQuoteFare'];
				$dTripDate = $tripsDetail[$i]['dTripDate'];
				$tripsDetail[$i]['dTripDate'] = date_create($tripsDetail[$i]['dTripDate']);
				$trips_detail[$i]['dTripDate'] = date_format($tripsDetail[$i]['dTripDate'], 'jS F Y g:i A');

				if($tripsDetail[$i]['dToDate']!='0000-00-00 00:00:00'){
					$tripsDetail[$i]['dToDate'] = date_create($tripsDetail[$i]['dToDate']);
					$trips_detail[$i]['dToDate'] = date_format($tripsDetail[$i]['dToDate'], 'jS F Y g:i A');
				}else{
					$trips_detail[$i]['dToDate'] = '';
				}

				if($tripsDetail[$i]['dRideEndDate']!='0000-00-00 00:00:00'){
					$tripsDetail[$i]['dRideEndDate'] = date_create($tripsDetail[$i]['dRideEndDate']);
					$trips_detail[$i]['dRideEndDate'] = date_format($tripsDetail[$i]['dRideEndDate'], 'jS F Y g:i A');
				}else{
					$trips_detail[$i]['dRideEndDate'] = '';
				}
				
				$trips_detail[$i]['eStatus'] = $tripsDetail[$i]['eStatus'];
				$trips_detail[$i]['iVehicleCompanyId'] = $tripsDetail[$i]['iVehicleCompanyId'];
				$trips_detail[$i]['vCompany'] = $tripsDetail[$i]['vCompany'];
				$trips_detail[$i]['vCarimage'] = $tripsDetail[$i]['vCarimage'];
				/*$trips_detail[$i]['vVehiclePlateNumber'] = $tripsDetail[$i]['vPlateNo'];*/
				$trips_detail[$i]['eDriverAssign'] = $tripsDetail[$i]['eDriverAssign'];
				$finalepayment = number_format($tripsDetail[$i]['fFinalPayment'],2);
				$trips_detail[$i]['fFinalPayment'] = $tripsDetail[$i]['vCurrencySymbol'].$finalepayment;
				$trips_detail[$i]['fDistance'] = $tripsDetail[$i]['fDistance'].' '.$tripsDetail[$i]['eDistanceUnit'];
				$trips_detail[$i]['fRating'] = $tripsDetail[$i]['fRating'];
				$trips_detail[$i]['eRatingStatus'] = $tripsDetail[$i]['eRatingStatus'];

				$startpointlatlonarr = explode('|', $tripsDetail[$i]['tPickUpAddressLatLong']);
				$trips_detail[$i]['vPickupLocation_Latitude'] = $startpointlatlonarr[0];
				$trips_detail[$i]['vPickupLocation_Longitude'] = $startpointlatlonarr[1];

				if ($tripsDetail[$i]['tDestinationAddressLatLong']!='') {
					$finishpointlatlonarr = explode('|', $tripsDetail[$i]['tDestinationAddressLatLong']);
					$trips_detail[$i]['vDestinationLocation_Latitude'] = $finishpointlatlonarr[0];
					$trips_detail[$i]['vDestinationLocation_Longitude'] = $finishpointlatlonarr[1];
				} else {
					$trips_detail[$i]['vDestinationLocation_Latitude'] = '';
					$trips_detail[$i]['vDestinationLocation_Longitude'] = '';
				}
				
				$finishpointlatlonarr = explode('|', $tripsDetail[$i]['tDestinationAddressLatLong']);
				$trips_detail[$i]['vDestinationLocation_Latitude'] = $finishpointlatlonarr[0];
				$trips_detail[$i]['vDestinationLocation_Longitude'] = $finishpointlatlonarr[1];

				$base_path = $this->data['base_path'];
				$file_path =$base_path.'uploads/car/'.$tripsDetail[$i]['iVehicleCompanyId'];
				if (file_exists($file_path)) {
					$trips_detail[$i]['image_url'] = $base_upload.'car/'.$tripsDetail[$i]['iVehicleCompanyId'].'/'.$tripsDetail[$i]['vCarimage'];
				}else{
					$trips_detail[$i]['image_url'] = "No_image_available";
				}
				$trips_detail[$i]['voFirstName'] = '';
				$trips_detail[$i]['voLastName'] = '';
				$trips_detail[$i]['voMobileNo'] = '';
				if($tripsDetail[$i]['eStatus']=='Cancel'){
					$trips_detail[$i]['eStatus'] = 'Cancelled';
				}
				else if($tripsDetail[$i]['eStatus']=='Complete'){
					$trips_detail[$i]['eStatus'] = 'Completed';
				}else if($tripsDetail[$i]['eStatus']=='Confirmed' && $tripsDetail[$i]['eDriverAssign']=='No'){
					// VO details 
					$ownerdetail = $this->webservices_model->getOwnerDetailbyId($tripsDetail[$i]['iVehicleOwnerId']);
					$trips_detail[$i]['voFirstName'] = $ownerdetail['vFirstName'];
					$trips_detail[$i]['voLastName'] = $ownerdetail['vLastName'];
					$trips_detail[$i]['voMobileNo'] = $ownerdetail['vMobileNo'];
				}
				$trips_detail[$i]['eDriverAssign'] = $tripsDetail[$i]['eDriverAssign'];
				$trips_detail[$i]['eStartTrip'] = $tripsDetail[$i]['eStartTrip'];
				$trips_detail[$i]['ePickUpRiderNow'] = $tripsDetail[$i]['ePickUpRiderNow'];
				$trips_detail[$i]['eGenderPreference'] = $tripsDetail[$i]['eGenderPreference'];
				$trips_detail[$i]['eSmokingPreference'] = $tripsDetail[$i]['eSmokingPreference'];
				$rideFeedback = $this->webservices_model->getRideFeedback($tripsDetail[$i]['iTripId']);
				$trips_detail[$i]['rideFeedback'] = $rideFeedback;
				$trips_detail[$i]['rideFeedbackArray'] = array();
				if (!empty($rideFeedback)) {
					$trips_detail[$i]['rideFeedbackArray'][] = $rideFeedback;
				}
				// for feedback button
				if(($tripsDetail[$i]['eStatus']=='Confirmed' || $tripsDetail[$i]['eStatus']=='Pending') && $tripsDetail[$i]['eStartTrip']=='Yes'){
					$tmpDate = new DateTime("now", new DateTimeZone($tripsDetail[$i]['vTimeZone']));
					$currentdatetime = $tmpDate->format('Y-m-d H:i:s');
					// Check time within 10 mins
					$to_time = strtotime($currentdatetime);
					$from_time = strtotime($dTripDate);
					$totalmins = round(abs($to_time - $from_time) / 60,2);
					// $this->printthis($totalmins);

					if($totalmins<=10 && empty($rideFeedback) ){
						$trips_detail[$i]['showFeedbackButton'] = "Yes";
					}else{
						$trips_detail[$i]['showFeedbackButton'] = "No";
					}
				}else{
					$trips_detail[$i]['showFeedbackButton'] = "No";
				}
			}
			if(count($trips_detail) > 0){
				$Data['data']=$trips_detail;
				$Data['msg']="Success";
			}else{
				$Data['msg']="No Record Found"; 
			}
		}else {
			$Data['msg']="Failure";
		}

		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';    
	}

	function getAllAvailability(){
		$eAvailability = field_enums('driver', 'eAvailability');
		$resArr = array();
		foreach ($eAvailability as $key => $value) {
			if($value=='Both'){
				$resArr[]='Both (Local / Outstation)';
			} else {
				$resArr[]=$value;
			}
		}
		$data['data'] = $resArr;
		$data['msg'] = "Success";
		header('Content-type: application/json');
		echo json_encode($data);
		exit;
	}

	function FinishTrip(){
		//************************************** New **************************************
		if(($_REQUEST['iTripId']) && ($_REQUEST['dLatitude']) && ($_REQUEST['dLongitude'])){
			$latesttriparr = array();
			$trip_id = $_REQUEST['iTripId'];
			$tripData = $this->webservices_model->get_single_trip_details($trip_id);
			$citydetail = $this->webservices_model->get_one_city_details_byid($tripData['iCityId']);
			$mycurrency = $this->webservices_model->get_city_currency($tripData['iCityId']);
			$tmpDate = new DateTime("now", new DateTimeZone($citydetail['vTimeZone']));
			$end_time = $tmpDate->format('Y-m-d H:i:s');
			
			$latesttriparr['ePaymentStatus'] = 'Completed';
			$latesttriparr['dRideEndDate'] = $end_time;
			// get destination address
			$latesttriparr['tDestinationAddressLatLong'] = $_REQUEST['dLatitude'].'|'.$_REQUEST['dLongitude'];
			$latesttriparr['vDestinationLocation'] = $this->GetAddressFromLatLong($_REQUEST['dLatitude'],$_REQUEST['dLongitude']);
			$starttripdatetime = date('Y-m-d H:i',strtotime($tripData['dTripDate']));
			$endtripdatetime = date('Y-m-d H:i',strtotime($latesttriparr['dRideEndDate']));
			if(strtotime($starttripdatetime) == strtotime($endtripdatetime)){
				$totalmins = 0;
			}else {
				$to_time = strtotime($endtripdatetime);
				$from_time = strtotime($starttripdatetime);
				$totalmins = round(abs($to_time - $from_time) / 60,2);
			}
			
			// code for calculate distance and duration
			$final_tracker_record = $this->webservices_model->get_trip_tracker_latlong($trip_id);
			$startlatlongarr = explode(',', $final_tracker_record['TrackerFinishLatlong']);
            $lat1 = $startlatlongarr[0];
            $lon1 = $startlatlongarr[1];
            $lat2 = $_REQUEST['dLatitude'];
            $lon2 = $_REQUEST['dLongitude'];
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            if ($final_tracker_record['eDistanceUnit']=='Miles') {
            	$totalkm = ($dist * 60 * 1.1515);
            }
            if ($final_tracker_record['eDistanceUnit']=='KMs') {
            	$miles = ($dist * 60 * 1.1515);
            	$totalkm = ($miles * 1.609344);
            }
            //$finaldistance = ($totalkm + $final_tracker_record['fDistance']);
            if($totalkm>1){
            	$finaldistance = ($totalkm);
			}else{
            	$finaldistance = ($totalkm + $final_tracker_record['fDistance']);
			}
			// end of code for calculate distance and duration
                
			$fixed_rate_final_payment = $mycurrency['vCurrencyCode'].' '.'0.00';
			$promo_data = $this->webservices_model->get_promo_code_details($tripData['iPromotionCodeId']);

			if($tripData['iRideId']==0){
				// This is not Fix ride
				$citydetail = $this->webservices_model->get_one_city_details_byid($tripData['iCityId']);
				$faredetail = $this->webservices_model->get_fare_quote_details_new($tripData['iCityId'],$tripData['iVehicleCompanyId'],$tripData['eTripLocation'],$tripData['eTripType']);
				if(count($faredetail)>0){
					$latesttriparr['fDistance'] = round($finaldistance,2);
					$latesttriparr['fTotalMinute'] = $totalmins;
					
					// 1. fare for local and one way : Done
					if ($tripData['eTripLocation']=='Local' && $tripData['eTripType']=='One Way') {
						if($finaldistance <= $faredetail['fMinimumKm']){
							$finalfare = $faredetail['fThreeKmFare'];
							// Assign values to save
							$latesttriparr['fPerMileFare'] = "0.00";
							$latesttriparr['fPerMinFare'] = "0.00";
							$latesttriparr['fBaseFare'] = $faredetail['fBaseFare'];
							$fServiceTax = (($finalfare * $citydetail['fServicetax'])/100);
							$finalfare = $finalfare + $fServiceTax;
							$latesttriparr['fServiceTax'] = $fServiceTax;
							$latesttriparr['dSubtotalPayment'] = round($finalfare, 2);
						}else {
							$perMileFareNew = 0;
							$totalfareinmins = ($faredetail['fPerMinFare'] * $totalmins);
							
							$current_hr_min=date('H:i', strtotime($tripData['dTripDate']));
							if ($citydetail['ePicksurchargestatus']=='Yes') {
								$vPicktimefrom = date('H:i', strtotime($citydetail['vPicktimefrom']));
								$vPicktimeto = date('H:i', strtotime($citydetail['vPicktimeto']));
								if ($this->timeisBetween($vPicktimefrom,$vPicktimeto,$current_hr_min))
								{
									$perMileFareNew=$faredetail['fPerMileFare']+(($faredetail['fPerMileFare']*$faredetail['fPeaktimesurcharge'])/100);
								}
							}
							if ($citydetail['eNightsurchargestatus']=='Yes') {
								$vNighttimefrom = date('H:i', strtotime($citydetail['vNighttimefrom']));
								$vNighttimeto = date('H:i', strtotime($citydetail['vNighttimeto']));
								if ($this->timeisBetween($vNighttimefrom,$vNighttimeto,$current_hr_min))
								{
									$perMileFareNew=$faredetail['fPerMileFare']+(($faredetail['fPerMileFare'] * $faredetail['fNighttimesurcharge']) / 100);
								}
							}
							$perMileFarefinal = ($perMileFareNew==0) ? $faredetail['fPerMileFare'] : $perMileFareNew ;
							
							$totalfareinmiles = ($perMileFarefinal * $finaldistance);
							
							$finalfare = ($totalfareinmiles+$totalfareinmins);
							$finalfare = ($finalfare + $faredetail['fBaseFare']);
							// $finalfare = $finalfare + (($finalfare * $citydetail['fServicetax'])/100);
							$fServiceTax = (($finalfare * $citydetail['fServicetax'])/100);
							$finalfare = $finalfare + $fServiceTax;
							$latesttriparr['fServiceTax'] = $fServiceTax;

							$latesttriparr['fPerMileFare'] = round($totalfareinmiles, 2);
							$latesttriparr['fPerMinFare'] = round($totalfareinmins,2);
							$latesttriparr['fBaseFare'] = $faredetail['fBaseFare'];
							$latesttriparr['dSubtotalPayment'] = round($finalfare,2);
						}
						$latesttriparr['MinimumFare'] = $faredetail['fThreeKmFare'];
					}
					// 2. fare for outstation and one way
					if ($tripData['eTripLocation']=='Outstation' && $tripData['eTripType']=='One Way') {
						if ($tripData['iOutOfStationId'] > 0) {
							// get discount %
							$outstationDiscount = $this->webservices_model->getOutOfStationDiscountByID($tripData['iOutOfStationId']);
							$fPerMileFare=$faredetail['fPerMileFare']-($faredetail['fPerMileFare']*($outstationDiscount['iAdditionalDiscount']/100));
						}else{
							$fPerMileFare = $faredetail['fPerMileFare'];
						}

						$minFare = $faredetail['fPerMileFare']*$faredetail['fMinimumKm'];
						if($finaldistance <= $faredetail['fMinimumKm']){
							$finalfare = $faredetail['fThreeKmFare'];
						}else {
							$finalfare = ($fPerMileFare * $finaldistance);
							if ($faredetail['fThreeKmFare'] > $finalfare) {
								$finalfare = $faredetail['fThreeKmFare'];
							}
						}
						// $finalfare = $finalfare + ($finalfare * ($citydetail['fServicetax']/100));
						$fServiceTax = (($finalfare * $citydetail['fServicetax'])/100);
						$finalfare = $finalfare + $fServiceTax;
						$latesttriparr['fServiceTax'] = $fServiceTax;

						$latesttriparr['fPerMileFare'] = "0.00";
						$latesttriparr['fPerMinFare'] = "0.00";
						$latesttriparr['fBaseFare'] = "0.00";
						$latesttriparr['dSubtotalPayment'] = round($finalfare,2);
						$latesttriparr['MinimumFare'] = $faredetail['fThreeKmFare'];
					}
					// 3. CarPoolLocal
					// 4. CarPoolOutstation
					if ($tripData['eTripLocation']=='CarPoolLocal' || $tripData['eTripLocation']=='CarPoolOutstation') {
						if ($tripData['iOutOfStationId'] > 0) {
							// get discount %
							$outstationDiscount = $this->webservices_model->getOutOfStationDiscountByID($tripData['iOutOfStationId']);
							$fPerMileFare=$faredetail['fPerMileFare']-($faredetail['fPerMileFare']*($outstationDiscount['iAdditionalDiscount']/100));
						}else{
							$fPerMileFare = $faredetail['fPerMileFare'];
						}

						$finalfare = ($fPerMileFare * $finaldistance);
						if ($faredetail['fThreeKmFare'] > $finalfare) {
							$finalfare = $faredetail['fThreeKmFare'];
						}
						$fServiceTax = (($finalfare * $citydetail['fServicetax'])/100);
						$finalfare = $finalfare + $fServiceTax;
						$latesttriparr['fServiceTax'] = $fServiceTax;
						$latesttriparr['fPerMileFare'] = $faredetail['fPerMileFare'];
						$latesttriparr['fPerMinFare'] = "0.00";
						$latesttriparr['fBaseFare'] = "0.00";
						$latesttriparr['dSubtotalPayment'] = round($finalfare,2);
						$latesttriparr['MinimumFare'] = $faredetail['fThreeKmFare'];
					}
					// 5. fare for local and round : Done
					if ($tripData['eTripLocation']=='LocalByDuration' && $tripData['eTripType']=='Round') {
						// to see old backup for this see 14_feb_2017 bkp
						$optionArr = explode('/', $tripData['vRoundOption']);
						
						$options_arr=explode(',',$faredetail['lOptions']);
						$fare=array();
						foreach ($options_arr as $key => $value) {
							$pos = strpos($value, $tripData['vRoundOption']);
							if ($pos !== false) {
							    $fare=explode('|',$value);
							}
						}
						$finalfare=(int)$fare[1];
						$minFare=$finalfare;
						// $finalfare = $finalfare + (($finalfare * $citydetail['fServicetax'])/100);
						$fServiceTax = (($finalfare * $citydetail['fServicetax'])/100);
						$finalfare = $finalfare + $fServiceTax;
						$latesttriparr['fServiceTax'] = $fServiceTax;
						// Assign values to save
						$latesttriparr['fPerMileFare'] = $faredetail['fPerMileFare'];
						$latesttriparr['fPerMinFare'] = "0.00";
						$latesttriparr['fBaseFare'] = "0.00";
						$latesttriparr['dSubtotalPayment'] = round($finalfare,2);
						$latesttriparr['MinimumFare'] = round($minFare,2);
					}
					// 6. fare for outstation and round
					if ($tripData['eTripLocation']=='OutStationByDuration' && $tripData['eTripType']=='Round') {
						$additionalFare=0;
						$totalmins = ($totalmins==0) ? 1 : $totalmins ;
						if($tripData['vRoundOption']=='Full Day'){
							$finalfare = ($faredetail['fPerMileFare'] * $faredetail['fMinimumKm']);
							$minFare = $finalfare;
							if($finaldistance > $faredetail['fMinimumKm']){
								$finalfare = $finalfare + ($faredetail['fPerMileFare'] * ($finaldistance - $faredetail['fMinimumKm']));
							}
						}/*else{
							$optionArr = explode(' ', $tripData['vRoundOption']);
							$dayPart = number_format(trim($optionArr[0]));
							$actualDays = ceil($totalmins/1440);
							$finalfare = ($faredetail['fPerMileFare'] * $faredetail['fMinimumKm']) * $actualDays;
							$minFare = $finalfare;
							if($finaldistance > ($faredetail['fMinimumKm'] * $actualDays)){
								$finalfare =$finalfare+ ($faredetail['fPerMileFare'] * ($finaldistance - ($faredetail['fMinimumKm'] * $actualDays)));
							}
							$additionalFare = ($actualDays-1)*$faredetail['fOvernightallowence'];
						}
						$finalfare =$finalfare+$additionalFare;*/

						else{
							$optionArr = explode(' ',$tripData['vRoundOption']);
							$dayPart = number_format(trim($optionArr[0]));
							$finalfare = ($faredetail['fPerMileFare'] * $faredetail['fMinimumKm']) * $dayPart;
							$minFare=$finalfare;
							if($finaldistance > ($faredetail['fMinimumKm'] * $dayPart)){
								$finalfare =$finalfare+ ($faredetail['fPerMileFare'] * ($finaldistance - ($faredetail['fMinimumKm'] * $dayPart)));
							}
							$additionalFare = ($dayPart-1)*$faredetail['fOvernightallowence'];
						}
						$finalfare =$finalfare+$additionalFare;
						// $finalfare = $finalfare + (($finalfare * $citydetail['fServicetax'])/100);
						$fServiceTax = (($finalfare * $citydetail['fServicetax'])/100);
						$finalfare = $finalfare + $fServiceTax;
						$latesttriparr['fServiceTax'] = $fServiceTax;
						$latesttriparr['fPerMileFare'] = $faredetail['fPerMileFare'];
						$latesttriparr['fPerMinFare'] = '';
						$latesttriparr['fBaseFare'] = '';
						$latesttriparr['dSubtotalPayment'] = round($finalfare,2);
						$latesttriparr['MinimumFare'] = round($minFare,2);
					}
					// 7. Shuttle
					if ($tripData['eTripLocation']=='Shuttle' && $tripData['eTripType']=='One Way') {
						$latesttriparr['fPerMileFare'] = 0;
						$latesttriparr['fPerMinFare'] = 0;
						$latesttriparr['fBaseFare'] = $tripData['MinimumFare'];
						$latesttriparr['fServiceTax'] = $tripData['fServiceTax'];
						$latesttriparr['dSubtotalPayment'] = round($tripData['dSubtotalPayment'],2);
						$finalfare=round($tripData['dSubtotalPayment'],2);
					}
				}else{
					$dataarr['msg'] = 'Failure';
					header('Content-type: application/json');
					$callback = '';
					if (isset($_REQUEST['callback'])){
					$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
					}
					$main = json_encode($dataarr);
					echo $callback . ''.$main.'';
					exit;
				}
			}else { 
				// fare calculation for fixed ride
				$latesttriparr['MinimumFare'] = '0.00';
				// code for calculate distance and duration
				// $finalkmminarr = $this->Get_DISTANCE_DURATION_FromLatLong_webservice($tripData['tPickUpAddressLatLong'],$latesttriparr['tDestinationAddressLatLong']);
				// end of code for calculate distance and duration
				$latesttriparr['fDistance'] = $finaldistance;
				$latesttriparr['fTotalMinute'] = $totalmins;
				$finalfare = $tripData['dSubtotalPayment'];
			}           
			// calculation for Discounts if any
				$finaltotalinvitationbookdiscount = 0;
				if($tripData['dInvitePromoCodeDiscount']>0){
					$finaltotalinvitationbookdiscount = $tripData['dInvitePromoCodeDiscount'];
				}

				$finaltotalpromobookdiscount = 0;
				if($promo_data){
					if($promo_data['eDiscountType']=='Percentage'){
						$percentage_promo = $promo_data['fDiscount'];
						$finaltotalpromobookdiscount = (($finalfare*$percentage_promo)/100);
						$latesttriparr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;                       
					}else if($promo_data['eDiscountType']=='Amount'){
						$finaltotalpromobookdiscount = $promo_data['fDiscount'];
						$latesttriparr['dNewsLetterPromocodeDiscount'] = $finaltotalpromobookdiscount;
					}
				}

				$finalinvitebookdiscount = ($finaltotalinvitationbookdiscount + $finaltotalpromobookdiscount);
				if($finalinvitebookdiscount>$finalfare){
					$latesttriparr['fFinalPayment'] = '0.00';
				}else {
					$latesttriparr['fFinalPayment'] = round(($finalfare - $finalinvitebookdiscount),2);
				}
			// calculation for Discounts ends

			// Final Fare
			$fixed_rate_final_payment = $mycurrency['vCurrencyCode'].' '.$latesttriparr['fFinalPayment'];
			// end of code for final fare calculation

			// $this->printthisexit($latesttriparr);
			// Update Trip details
			$totalrows = $this->webservices_model->update_trip_details($trip_id,$latesttriparr);
			// save record for transactionid
			$trip_details_transaction = $this->webservices_model->get_single_trip_details_for_transaction($trip_id);

			$paypaltransarr = array();
			if($trip_details_transaction['ePaymentType']=='Credit Card'){
				$riderCreditCardDetails = $this->webservices_model->get_card_info($trip_details_transaction['iCustomerCreditCardId']);
				$paypaltransarr['iDriverId'] = $trip_details_transaction['iDriverId'];
				$paypaltransarr['iCardTypeId'] = $riderCreditCardDetails['iCardTypeId'];
				$paypal_rider_id = $trip_details_transaction['iClientId'];
				$paypal_final_payment = $trip_details_transaction['fFinalPayment'];
				$paypaltransarr['fAmount'] = $paypal_final_payment;
				$paypaltransarr['fCompanyAmount'] = $paypal_final_payment;
				$paypaltransarr['eType'] = 'Credit';
				$paypaltransarr['dDate'] = date('Y-m-d H:i:s');
				$paypaltransarr['ePayStatus'] = 'Not Paid';
				$payment_type = 'Credit Card';
			}else {
				$paypaltransarr['iDriverId'] = $trip_details_transaction['iDriverId'];
				$paypaltransarr['iCardTypeId'] = '0';
				$paypal_final_payment = $trip_details_transaction['fFinalPayment'];
				$paypaltransarr['fAmount'] = $paypal_final_payment;
				$paypaltransarr['fCompanyAmount'] = $paypal_final_payment;
				$paypaltransarr['eType'] = 'Credit';
				$paypaltransarr['vPaypalTransactionId'] = '0';
				$paypaltransarr['dDate'] = date('Y-m-d H:i:s');
				$paypaltransarr['ePayStatus'] = 'Not Paid';
				$payment_type = 'Cash';
			}
			// end of code for transation
			$stripepaymentflag = 0;
			$stripepaymentmessage = '';
			if($trip_details_transaction['ePaymentType']=='Credit Card'){
				if(intval($paypal_final_payment)>0){
					$striperesarr = $this->stripepayment($riderCreditCardDetails['iTransactionId'],$paypal_rider_id,$paypal_final_payment,$mycurrency['vCurrencyCode']);
					if($striperesarr['error_status']=='yes'){
						$stripepaymentflag = 0;
						$stripepaymentmessage = $striperesarr['message'];
					}else {
						$stripepaymentflag = 1;
						$stripepaymentmessage = $striperesarr['message'];
						$paypaltransarr['vPaypalTransactionId'] = $striperesarr['message'];
					}
				}else {
					$stripepaymentflag = 1;    
				}
			}else {
				$stripepaymentflag = 1;
			}

			if($stripepaymentflag==1){
				$tripcompletedarr['eStatus'] = 'Complete';
				$totalrowsupd = $this->webservices_model->update_trip_details($trip_id,$tripcompletedarr);
				// send notification to customer
				$customer_device_info = $this->webservices_model->get_rider_device_details_by_id($tripData['iClientId']);
				$pushNotificationData['action'] = 'sendNotification';
				$pushNotificationData['msg'] = "Thanks for using Onetouchcab! *****".$trip_id;
				$pushNotificationData['vDeviceid'] = $customer_device_info['device_id'];
				$pushNotificationData['eUserType'] = "Rider";
				$datapush = $this->pushNotification($pushNotificationData);
				// end of code for send notification

				$iTransactionId = $this->webservices_model->save_transaction_details($paypaltransarr);
				$totaltransrows = $this->webservices_model->update_transaction_id($payment_type,$iTransactionId,$trip_id);
				
				//************ get latest detail of trip *******************************************************
				$tripDetails = $this->webservices_model->get_single_trip_details($trip_id);

				$traveldate = $tripDetails['dTripDate'];
				$traveldate = date_create($tripDetails['dTripDate']);
				$traveldate = date_format($traveldate, 'jS F Y');
				$route_map_url = $tripDetails['tRouteImgURL'];
				$line_img_url = $this->data['base_url'].'assets/image/invoice/shep.png';
				$red_marker_url = $this->data['base_url'].'assets/image/invoice/red.png';
				$green_marker_url = $this->data['base_url'].'assets/image/invoice/green.png';
				$tripstarttime = date_create($tripDetails['dTripDate']);
				$tripstarttime = date_format($tripstarttime, 'g:i A');
				$source_address = $tripDetails['vPickupLocation'];
				$tripendtime = date_create($tripDetails['dRideEndDate']);
				$tripendtime = date_format($tripendtime, 'g:i A');
				$destination_address = $tripDetails['vDestinationLocation'];
				$car_name = $tripDetails['vModelName'];
				$trip_full_start_time = date('H:i:s',strtotime($tripDetails['dTripDate']));
				$my_base_url = $this->data['base_url'];
				// code for displaying trip
				$trip_star_rating_display = $this->StarRating($tripDetails['fRating']);
				// end of code for displaying trip

				$travel_fare = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fFinalPayment'],2);
				$total_distance_in_miles = $tripDetails['fDistance'].' '.strtoupper($tripDetails['eDistanceUnit']);

				if($tripDetails['fTotalMinute']){
					$tripDetails['fTotalMinute'] = ceil($tripDetails['fTotalMinute']);
					if($tripDetails['fTotalMinute']<=1){
						$total_duration_in_mins = ceil($tripDetails['fTotalMinute']).' MIN';
					}else {
						$fTotalMinute = ceil($tripDetails['fTotalMinute']);
						if($fTotalMinute>=60){
							$hours = intval($fTotalMinute/60);
							$minutes = $fTotalMinute - ($hours * 60);

							if($hours>1){
								if($minutes>1){ $total_duration_in_mins = $hours.' HRS '.$minutes.' MINS'; }
								else { $total_duration_in_mins = $hours.' HRS '.$minutes.' MIN'; }
							}else{
								if($minutes>1){ $total_duration_in_mins = $hours.' HR '.$minutes.' MINS'; }
								else { $total_duration_in_mins = $hours.' HR '.$minutes.' MIN'; }
							}
						}else {
							$total_duration_in_mins = ceil($tripDetails['fTotalMinute']).' MINS';
						}
					}
				}else {
					$total_duration_in_mins = '0 MIN';    
				}

				$base_fare = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fBaseFare'], 2);
				$distance_fare = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fPerMileFare'], 2);
				$duration_fare = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fPerMinFare'], 2);
				$sub_total = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['dSubtotalPayment'], 2);
				$saferidefees = $mycurrency['vCurrencyCode'].' '.number_format(($tripDetails['dNewsLetterPromocodeDiscount'] + $tripDetails['dInvitePromoCodeDiscount']),2);
				$minpayment = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['MinimumFare'],2);
				$finalpayment = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fFinalPayment'],2);
				if($tripDetails['vProfileImage']){
					$driver_profile_img = $this->data['base_upload'].'driver/'.$tripDetails['iDriverId'].'/'.$tripDetails['vProfileImage']; 
				}else {
					$driver_profile_img = $this->data['base_upload'].'red-driver.png';
				}
				
				$driver_name = $tripDetails['vFirstName'].' '.$tripDetails['vLastName'];
				$credit_card_image_name = $this->webservices_model->get_card_image_name($tripDetails['iTransactionId']);

				if($credit_card_image_name){
					$card_url = $this->data['base_upload'].'credit_card/'.$credit_card_image_name['vCardImage'];
				}else{
					$card_url = $this->data['base_upload'].'credit_card/cash.png';
				}
				$emailmins = $this->ConvertHrToMin($totalmins);
				$all_partners = $this->webservices_model->get_all_trip_partners($tripDetails['iClientId'],$trip_id);
				for($a=0;$a<count($all_partners);$a++){
					$all_internal_partners_names = $this->webservices_model->get_all_trip_internal_partners($all_partners[$a]['iClientId'],$trip_id);
					
					$internalpartnernames = '';
					if(count($all_internal_partners_names)==1){
						$internalpartnernames = 'You rode with partner '.$all_internal_partners_names[0]['vFirstName'].' '.$all_internal_partners_names[0]['vLastName'].' and '.$tripDetails['ClientFirstName'].' '.$tripDetails['ClientLastName']; 
					}else if(count($all_internal_partners_names)>1){
						for($i=0;$i<count($all_internal_partners_names);$i++){
							if(($i==0) && (count($all_internal_partners_names)!=2)){
								$internalpartnernames = 'You rode with partner '.$internalpartnernames.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'].',';
							}else if(($i==0) && (count($all_internal_partners_names)==2)){
								$internalpartnernames = 'You rode with partner '.$internalpartnernames.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'];
							}else if($i==(count($all_internal_partners_names)-1)){

								$internalpartnernames = $internalpartnernames.','.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'];
							}else if($i==(count($all_internal_partners_names)-2)){
								$internalpartnernames = $internalpartnernames.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'];
							}else {
								$internalpartnernames = $internalpartnernames.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'].','; 
							}
						}
						$internalpartnernames = $internalpartnernames.' and '.$tripDetails['ClientFirstName'].' '.$tripDetails['ClientLastName'];   
					}else if(count($all_internal_partners_names)==0){
						$internalpartnernames = 'You rode with partner '.$tripDetails['ClientFirstName'].' '.$tripDetails['ClientLastName'];
					}

					$bodyArr = array("#CARD_IMAGE#","#TRAVELDATE#","#MAP_IMG_URL#","#LINE_IMG_URL#","#RED_MARKER_IMG#","#GREEN_MARKER_IMG#","#TRIP_START_TIME#","#SOURCE_ADDRESS#","#TRIP_END_TIME#","#DESTINATION_ADDRESS#","#CAR_NAME#","#TRIP_FULL_START_TIME#","#TRAVEL_FARE#","#TOTAL_DISTANCE_IN_MILES#","#TOTAL_TIMES_IN_MINS#","#SUBTOTAL#","#DISCOUNTS#","#FINAL_PAYMENT#","#DRIVER_PROFILE_IMG#","#DRIVER_NAME#","#PARTNER_NAME#","#BASE_FARE#","#DISTANCE_FARE#","#DURATION_FARE#","#STAR_RATING#","#IMAGE_URL#","#FIXED_RATE#");
					$postArr = array($card_url,$traveldate,$route_map_url,$line_img_url,$red_marker_url,$green_marker_url,$tripstarttime,$source_address,$tripendtime,$destination_address,$car_name,$trip_full_start_time,$travel_fare,$total_distance_in_miles,$emailmins,$sub_total,$saferidefees,$finalpayment,$driver_profile_img,$driver_name,$internalpartnernames,$base_fare,$distance_fare,$duration_fare,$trip_star_rating_display,$my_base_url,$minpayment);

					$sendClient=$this->Send("CUSTOMER_INVOICE","Client",$all_partners[$a]['vEmail'],$bodyArr,$postArr); 
				}


				$partnernames = '';
				$all_partners_names = $this->webservices_model->get_all_trip_partners_info($tripDetails['iClientId'],$trip_id);
				if(count($all_partners_names)==1){
					$partnernames = 'You rode with partner '.$all_partners_names[0]['vFirstName'].' '.$all_partners_names[0]['vLastName'];  
				}else if(count($all_partners_names)>1){
					for($i=0;$i<count($all_partners_names);$i++){
						if(($i==0) && (count($all_partners_names)!=2)){
							$partnernames = 'You rode with partner '.$partnernames.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'].',';
						}else if(($i==0) && (count($all_partners_names)==2)){
							$partnernames = 'You rode with partner '.$partnernames.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'];
						}else if($i==(count($all_partners_names)-1)){
							$partnernames = $partnernames.' and '.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'];
						}else if($i==(count($all_partners_names)-2)){
							$partnernames = $partnernames.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'];
						}else {
							$partnernames = $partnernames.$all_partners_names[$i]['vFirstName'].' '.$all_partners_names[$i]['vLastName'].',';   
						}
					}   
				}else if(count($all_partners_names)==0){
					$partnernames = '';
				}

				$bodyArr = array("#CARD_IMAGE#","#TRAVELDATE#","#MAP_IMG_URL#","#LINE_IMG_URL#","#RED_MARKER_IMG#","#GREEN_MARKER_IMG#","#TRIP_START_TIME#","#SOURCE_ADDRESS#","#TRIP_END_TIME#","#DESTINATION_ADDRESS#","#CAR_NAME#","#TRIP_FULL_START_TIME#","#TRAVEL_FARE#","#TOTAL_DISTANCE_IN_MILES#","#TOTAL_TIMES_IN_MINS#","#SUBTOTAL#","#DISCOUNTS#","#FINAL_PAYMENT#","#DRIVER_PROFILE_IMG#","#DRIVER_NAME#","#PARTNER_NAME#","#BASE_FARE#","#DISTANCE_FARE#","#DURATION_FARE#","#STAR_RATING#","#IMAGE_URL#","#FIXED_RATE#");
				$postArr = array($card_url,$traveldate,$route_map_url,$line_img_url,$red_marker_url,$green_marker_url,$tripstarttime,$source_address,$tripendtime,$destination_address,$car_name,$trip_full_start_time,$travel_fare,$total_distance_in_miles,$emailmins,$sub_total,$saferidefees,$finalpayment,$driver_profile_img,$driver_name,$internalpartnernames,$base_fare,$distance_fare,$duration_fare,$trip_star_rating_display,$my_base_url,$minpayment);

				$client_email_address = $tripDetails['ClientEmail'];
				$sendClient=$this->Send("CUSTOMER_INVOICE","Client",$client_email_address,$bodyArr,$postArr);
				// end of temp code
				$dataarr['data'] = '';
				$dataarr['msg'] = 'Success';
			}else {
				$dataarr['data'] = $stripepaymentmessage;
				$dataarr['msg'] = 'Failure';
			}
		}else {
			$dataarr['data'] = '';
			$dataarr['msg'] = 'Failure';
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($dataarr);
		echo $callback . ''.$main.'';
		exit;
	}

	function rideFeedback(){
		if($this->input->post('iTripId') && $this->input->post('eQualityOfVehicle') && $this->input->post('eDriverNature')){
			$addData['iTripId']=$this->input->post('iTripId');
			$check = $this->webservices_model->check_Exist('iTripId', $addData['iTripId'], 'ride_feedback');
			if ($check > 0) {
				$response['msg'] = 'Your feedback is already available for this trip';
				echo json_encode($response);exit;
			}
			$tripsDetail = $this->webservices_model->getTripCityTimezoneVehicle($addData['iTripId']);
			// $this->printthis($tripsDetail);
			$tmpDate = new DateTime("now", new DateTimeZone($tripsDetail['vTimeZone']));
			$currentdatetime = $tmpDate->format('Y-m-d H:i:s');
			// Check time within 10 mins
			$to_time = strtotime($currentdatetime);
			$from_time = strtotime($tripsDetail['dTripDate']);
			$totalmins = round(abs($to_time - $from_time) / 60,2);
			// $this->printthis($totalmins);
			if ($totalmins<=10) {
				$addData['eQualityOfVehicle']=$this->input->post('eQualityOfVehicle');
				$addData['eDriverNature']=$this->input->post('eDriverNature');
				$addData['tDescription']=$this->input->post('tDescription');
				$addData['iVehicleAttributeId'] = $tripsDetail['iVehicleAttributeId'];
				$addData['dtAddedDate'] = $currentdatetime;
				// $this->printthisexit($addData);
				$res = $this->webservices_model->saveRideFeedback($addData);
				// Send notification to client
				$customer_device_info = $this->webservices_model->get_rider_device_details_by_id($tripsDetail['iClientId']);
				$pushNotificationData['action'] = 'sendNotification';
				$pushNotificationData['msg'] = 'Your feed has been delivered to the Vehicle Owner and also to the OneTouchCab site admin for immidiate action.';
				$pushNotificationData['vDeviceid'] = $customer_device_info['device_id'];
				$pushNotificationData['eUserType'] = "Rider";
				$datapush = $this->pushNotification($pushNotificationData);
				// Send notification to VO / Admin if Bad
				if ($addData['eQualityOfVehicle']=='Bad' || $addData['eDriverNature']=='Bad') {
					if ($addData['eQualityOfVehicle']=='Bad' && $addData['eDriverNature']=='Bad') {
						$notemsg = "We receive feedback as bad quality of vehicle ".$tripsDetail['vRegistrationNo'].' and bad nature of driver '.$tripsDetail['dfname'].' '.$tripsDetail['dlname'];
					}else if ($addData['eQualityOfVehicle']=='Bad') {
						$notemsg = "We receive feedback as bad quality of vehicle ".$tripsDetail['vRegistrationNo'];
					}else if ($addData['eDriverNature']=='Bad') {
						$notemsg = "We receive feedback as bad nature of driver ".$tripsDetail['dfname'].' '.$tripsDetail['dlname'];
					}
					$ownerDevice = $this->webservices_model->getOwnerDevice($ownerID['iVehicleOwnerId']);
					$pushNotificationData['action'] = 'sendNotification';
					$pushNotificationData['msg'] = $notemsg;
					$pushNotificationData['vDeviceid'] = $ownerDevice['device_id'];
					$pushNotificationData['eUserType'] = "Owner";
					$datapush = $this->pushNotification($pushNotificationData);
					// mail to site admin
				}
				$response['msg'] = 'Success';
			} else {
				$response['msg'] = 'You can give feedback within 10 mins after trip start';
			}
		}else{
			$response['msg'] = 'Failure';
		}
		echo json_encode($response);
		exit;
	}

	function addCarPool(){
		// Save data
		if($this->input->post('iDriverId') && $this->input->post('vFromAddress') && $this->input->post('vToAddress') && $this->input->post('dFromDate') && $this->input->post('vTimeGap') && $this->input->post('eType')){
			// eType[OneTime / Regular]
			$givenData = $this->input->post();
			if ($givenData['eType']=='Regular'){
				if ($givenData['days']==''){
					$data['msg'] = "Failure";
					echo json_encode($data);exit;
				}else{
					// days [Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday / All]
					if ($givenData['days']=='All') {
						$dayArr[] = 'All';
					} else {
						$dayArr = explode('|', $givenData['days']);
					}
				}
			}else{
				$trimmed = trim($givenData['vTimeGap'], '+-/');
				$addPool['dExpireDate'] = date('y-m-d H:i:s', strtotime($givenData['dFromDate']."+ ".$trimmed));
			}
			// Get City, lat-long from both address
			$fromCity = $this->getCityFromLatLongAddress('Address',$givenData['vFromAddress']);
			$toCity = $this->getCityFromLatLongAddress('Address',$givenData['vToAddress']);
			/*$this->printthis('fromCity');
			$this->printthis($fromCity);
			$this->printthis('toCity');
			$this->printthis($toCity);*/
			$addPool['iDriverId']=$givenData['iDriverId'];
			$addPool['vFromAddress']=$givenData['vFromAddress'];
			$addPool['vFromLatLong']=$fromCity['address_lat_long'];
			$flat_long = explode('|', $fromCity['address_lat_long']);
			$addPool['vFromLat']=$flat_long[0];
			$addPool['vFromLong']=$flat_long[1];
			$addPool['vFromCity']=$fromCity['vCity'];
			$addPool['vToAddress']=$givenData['vToAddress'];
			$addPool['vToCity']=$toCity['vCity'];
			$addPool['vToLatLong']=$toCity['address_lat_long'];
			$tolat_long = explode('|', $toCity['address_lat_long']);
			$addPool['vToLat']=$tolat_long[0];
			$addPool['vToLong']=$tolat_long[1];
			$addPool['dFromDate']=date('Y-m-d H:i:s',strtotime($givenData['dFromDate']));
			$addPool['eType']=$givenData['eType'];
			$addPool['vTimeGap']=$givenData['vTimeGap'];
			/*$this->printthis('addPool');
			$this->printthisexit($addPool);*/
			$iCarpoolId = $this->webservices_model->save_data($addPool, 'carpools');
			if($givenData['eType']=='Regular'){
				$addPoolDay['iCarpoolId']=$iCarpoolId;
				foreach ($dayArr as $dkey => $day) {
					$addPoolDay['eDays']=$day;
					$iCarpoolDayId = $this->webservices_model->save_data($addPoolDay, 'carpool_days');
				}
			}
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "Failure";
		}
		echo json_encode($data);exit;
	}

	// Helper for find city
	function getCityFromLatLongAddress($type,$value){
		if ($type=='Address') {
			$startlatlong = $this->GetLatLongFromAddress($value);
		} else {
			$startlatlong = $value;
		}
		$source_lat_long_arr = explode('|', $startlatlong);
		$dLatitude=$source_lat_long_arr[0];
		$dLongitude=$source_lat_long_arr[1];
		
		$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKM($dLatitude,$dLongitude);
		$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMile($dLatitude,$dLongitude);
		// check within radius
		$check_km=($source_city_status_km['distance'] <= $source_city_status_km['fRadius']) ? $source_city_status_km : 0;
		$check_mile = ($source_city_status_mile['distance'] <= $source_city_status_mile['fRadius']) ? $source_city_status_mile : 0;
		if($check_km != 0 || $check_mile != 0){
			if($check_km != 0 && $check_mile == 0){
				$source_citydetail = $check_km;
			}else if($check_mile != 0 && $check_km == 0){
				$source_citydetail = $check_mile;
			}else if($check_mile != 0 && $check_km != 0){
				$distanceFromKM = $check_km['distance'];
				$distanceFromMile=$check_mile['distance']*1.609344;
				$source_citydetail = ($distanceFromKM < $distanceFromMile) ? $check_km : $check_mile ;
			}else{
				$data['msg'] = "Service Not Available For Selected Address";
				echo json_encode($data);exit;
			}
		}else{
			$data['msg'] = "Service Not Available For Selected Address";
			echo json_encode($data);exit;
		}
		$source_citydetail['address_lat_long']=$startlatlong;
		return $source_citydetail;
	}

	// helper for get allcar : fixed ride
	function getAllFixedCars($ride_id,$carid){
		$base_upload=$this->data['base_upload'];
		$rideDetail = $this->webservices_model->get_ride_detail($ride_id);
		$vehicleDetails = $this->webservices_model->get_fix_ride_fare_by_ride_id($ride_id);
		$sourceCityDetail = $this->webservices_model->get_one_city_details_byid($rideDetail['iCityId']);
		if ($rideDetail['eFaretype']=='Local') {
			$ridelocationArr[] = 'Local';
			$ridelocationArr[] = 'Both';
		} else {
			$ridelocationArr[] = 'Outstation';
			$ridelocationArr[] = 'Both';
		}
		//--------------
		$source_lat_long = explode('|', $sourceCityDetail['tCityLatLong']);
		$qurVar = ($sourceCityDetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
		
		$drivers = $this->webservices_model->get_all_drivers_within_city($source_lat_long[0],$source_lat_long[1],$sourceCityDetail['fRadius'],$qurVar,$ridelocationArr,$carid);
		// $this->printthis("drivers");
		// $this->printthis($drivers);
		$availableDriverCount = 0;
		$companyArr = array();
		$availableDriver=array();
		foreach ($drivers as $drkey => $driver) {
			$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($driver['iDriverId']);
			// $this->printthis("countRunningTrip");
			// $this->printthis($countRunningTrip);
			if($countRunningTrip==0){
				$availableDriver[]=$driver;
				$availableDriverCount++;
				$vehicleCompany=$this->webservices_model->get_vehicle_by_model($driver['iModelId']);
				// $this->printthis("vehicleCompany");
				// $this->printthis($vehicleCompany);
				if (!in_array($vehicleCompany['iVehicleCompanyId'], $companyArr)) {
					$companyArr[]=$vehicleCompany['iVehicleCompanyId'];
				}
			}
		}
		if(count($availableDriver) > 0){
			$Data['data']= $availableDriver;
			$Data['msg'] = "Success";
		}else{
			$Data['msg'] = "No Car Found in Pick UP Location";
		}
		$main = json_encode($Data);
		echo $main;exit;
	}

	function getCarPoolList(){
		if($this->input->post('iDriverId')){
			// Get pool entries
			$allPools = $this->webservices_model->getCarPoolByDriver($this->input->post('iDriverId'));
			if (count($allPools)==0) {
				$data['msg'] = "No Record Found";
				echo json_encode($data);exit;
			}
			foreach ($allPools as $cpkey => $carpool){
				$carPoolDays='';
				if ($carpool['eType']=='Regular'){
					$poolDays = $this->webservices_model->getCarPoolDays($carpool['iCarpoolId']);
					foreach ($poolDays as $dkey => $poolDay){
						$carPoolDays.=$poolDay['eDays'].', ';
					}
					$carPoolDays = rtrim($carPoolDays, ", ");
				}
				$allPools[$cpkey]['carPoolDays']=$carPoolDays;
			}
			$data['data'] = $allPools;
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "Failure";
		}
		echo json_encode($data);exit;
	}

	function removeCarPool(){
		if($this->input->post('iCarpoolId')){
			$this->webservices_model->removeCarPoolById($this->input->post('iCarpoolId'));
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "Failure";
		}
		echo json_encode($data);exit;
	}

	function AutoExpireCarpool(){
		// Cron for Auto Expire onetime Carpool : called every 10 mins
		$oneTimePools = $this->webservices_model->getOneTimePools();
		foreach ($oneTimePools as $pkey => $pool) {
			$expire_time = strtotime($pool['dExpireDate']);
			$tmpDate = new DateTime("now", new DateTimeZone($pool['vTimeZone']));
			$now_time = $tmpDate->format('Y-m-d H:i:s');
			$now_compare =  strtotime($now_time);
			if($now_compare > $expire_time){
				$this->webservices_model->makeExpirePool($pool['iCarpoolId']);
			}
		}
		echo "Success";exit;
	}

	function mytest(){
		// echo "HTTP_HOST : ".$_SERVER["HTTP_HOST"];exit;
		// $this->printthisexit($_SERVER);
		/*$all_trips_by_driver=$this->webservices_model->checkRunningTripByDriver(135);
		$this->printthis("Res");
		$this->printthisexit($all_trips_by_driver);*/
	}
	// **************************** New Functions End ****************************

	// **************************** Changes for Phase 3 ****************************
	function riderRegistration(){
		$this->load->helper('string');
		if($this->input->post('type')){
			if($this->input->post('type')=='simple'){
				if($this->input->post('vFirstName') && $this->input->post('vLastName') && $this->input->post('vEmail') && $this->input->post('vPassword') && $this->input->post('iMobileNo')){

					$rider['vFirstName'] = $this->input->post('vFirstName');
					$rider['vLastName'] = $this->input->post('vLastName');
					$rider['vEmail'] = $this->input->post('vEmail');

					$password = $this->input->post('vPassword');
					$rider['vPassword'] = encrypt($this->input->post('vPassword'));
					$rider['vActivationCode']=rand_str();
					$rider['iOTP'] = random_string($type = 'numeric', 4);
					$rider['eStatus']="Inactive";
					$rider['dRegisterDate'] = date('Y-m-d');
					$rider['eImageType'] = '';
					
					$vEmail = trim($rider['vEmail']);
					$check_client_exists = $this->webservices_model->check_email_exists("client",$vEmail);
					$check_driver_exists = $this->webservices_model->check_email_exists("driver",$vEmail);

					$iMobileNo = $this->input->post('iMobileNo');
					/*$extraChr = array(" ", "(", ")", "-", "[", "]");*/
					/*$iMobileNo = str_replace($extraChr, "", $iMobileNo);*/
					$clientMobileExist = $this->webservices_model->getDetailByFieldValue('client','iMobileNo',$iMobileNo);
					if($clientMobileExist){
						$data['msg'] = "Mobile Number Exist";
						header('Content-type: application/json');
						$main = json_encode($data);
						echo $main;
						exit;
					}
					$rider['iMobileNo'] = $iMobileNo;

					if ($check_driver_exists==0 && $check_client_exists==0) {
						/*echo "<pre>";print_r($rider);
						print_r($payment);exit;*/
						$iClientId= $this->webservices_model->save_data($rider,"client");
						$new_client['iClientId']=$iClientId;
						$new_client['vPromotionCode']=str_pad($iClientId,6,"0",STR_PAD_LEFT);
						// Upload Image
						if($_FILES['vProfileImage']['name']!=''){
							$profileImageName = $this->clean($_FILES['vProfileImage']['name']);
							$img_uploaded_leads = $this->do_upload_user_profile_photo($iClientId,$profileImageName,'vProfileImage');
							$new_client['eImageType'] = 'withouturl';
							$new_client['vProfileImage'] = $img_uploaded_leads;
						}
						$result = $this->webservices_model->update_client_detail($new_client);
						
						if ($iClientId) {
							// Send Email
							$siteurl=$this->config->item('base_url');
							$MailFooter = $this->data['MAIL_FOOTER']; 
							$adminEmailId= $this->data['EMAIL_ADMIN'];
							$siteName = $this->data['SITE_NAME'];
							$FirstName=ucfirst($rider['vFirstName']);
							$LastName=ucfirst($rider['vLastName']);
							$link=$siteurl.'login/confirm_email?code='.$rider['vActivationCode'];
							$name1 =$FirstName.' '.$LastName.',';
							$name=ucfirst($name1);
							$bodyArr = array("#NAME#","#PASSWORD#","#EMAIL#","#SITEURL#","#MAILFOOTER#","#SITE_NAME#","#LINK#","#FIRSTNAME#","#LASTNAME#");
							$postArr = array($name,$password,$vEmail,$siteurl,$MailFooter,$siteName,$link,$FirstName,$LastName);  
							$sendClient=$this->Send("NEW_RIDER_REGISTER","Client",$vEmail,$bodyArr,$postArr);
							$sendAdmin=$this->Send("ADMIN_NEW_RIDER_REGISTER","Admin",$adminEmailId,$bodyArr,$postArr);
							// Send SMS
							$smsText = 'Your OneTouchCab Customer verification code is: '.$rider['iOTP'];
							// Send to test numbers
							require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
							$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
							$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
							$client = new Services_Twilio($AccountSid, $AuthToken);

							/*$people = array(
		        				"+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => ""
		    				);*/
		    				
		    				$extraChr = array(" ", "(", ")", "-", "[", "]");
							$update_rider_no = '+'.str_replace($extraChr, "", $iMobileNo);
							/*foreach ($people as $number => $name) { */
								try {
									$sms[] = $client->account->messages->sendMessage("+18562882821",$update_rider_no,$smsText);
									$data['msg'] = "Success";

								} catch (Exception $e) {
								    // echo 'Caught exception: ',  $e->getMessage(), "\n";
									$data['msg'] = "Failure";
								}
							/*}*/
							//-----------------------------------------------------

							$clientDetail = $this->webservices_model->get_single_client_details_without_status($iClientId);
							$clientDetail['iClientId'] = $iClientId;
							$clientDetail['fullname'] = $clientDetail['vFirstName'].' '.$clientDetail['vLastName'];         
							if(!empty($clientDetail)){
								if($clientDetail['eImageType']=='withurl'){
									$clientDetail['image_Url'] = $clientDetail['vProfileImage'];    
								}else if($clientDetail['eImageType']=='withouturl'){
									if ($clientDetail['vProfileImage']) {
										$clientDetail['image_Url'] = $this->data['base_url'].'uploads/client/'.$clientDetail['iClientId'].'/'.$clientDetail['vProfileImage'];
									}else{
										$clientDetail['image_Url'] = $this->data['base_url'].'uploads/plash-holder.png';
									}
								}else {
									$clientDetail['image_Url'] = $this->data['base_url'].'uploads/plash-holder.png';
								}
								$clientDetail['vPassword']= $this->decrypt($clientDetail['vPassword']);
								$data['data']=$clientDetail;
								$data['msg'] = "Registered Successfully";
							}else {
								$data['msg'] = "Failure";   
							}
						}else{
							$data['msg'] = "Registeration Error";
						}
					}else{
						$data['msg'] = "Email Address Exist";
					}
				}else{
					$data['msg'] = "Failure";
				}
			}else if($this->input->post('type')=='facebook'){
				if($this->input->post('fullname') && $this->input->post('vEmail') && $this->input->post('biFBId')){
					$rider['biFBId'] = $this->input->post('biFBId');
					$fullname = explode(" ",$this->input->post('fullname'));
					$rider['vFirstName'] = $fullname[0];
					if ($fullname[1]) {
						$rider['vLastName'] = $fullname[1];
					}
					$rider['vEmail'] = $this->input->post('vEmail');
					$rider['vCountryMobileCode'] = '+1';
					if ($this->input->post('iMobileNo')) {
						$rider['iMobileNo'] = $this->input->post('iMobileNo');
					}
					$rider['eStatus'] = "Active";
					$rider['dRegisterDate'] = date('Y-m-d');
					$vEmail = trim($rider['vEmail']);
					
					$check_client_exists = $this->webservices_model->check_email_exists("client",$vEmail);
					$check_driver_exists = $this->webservices_model->check_email_exists("driver",$vEmail);
					
					if ($check_driver_exists==0 && $check_client_exists > 0) {
						$iClientId = $this->webservices_model->getClientId('client',$vEmail);
						if ($iClientId['iClientId']) {

							$myclientid = $iClientId['iClientId'];
							
							$rider['iClientId'] = $iClientId['iClientId'];
							$rider['vPromotionCode']=str_pad($rider['iClientId'],6,"0",STR_PAD_LEFT);

							$ClientIdrows = $this->webservices_model->update_client_detail($rider);
							/*$iClientId = $iClientId['iClientId'];*/

							$clientDetail = $this->webservices_model->get_single_client_details_without_status($myclientid);
							
							$clientDetail['iClientId'] = "$myclientid";

							$clientDetail['fullname'] = $clientDetail['vFirstName'].' '.$clientDetail['vLastName'];         
							if(!empty($clientDetail)){

								if($clientDetail['eImageType']=='withurl'){
									$clientDetail['image_Url'] = $clientDetail['vProfileImage'];    
								}
								else if($clientDetail['eImageType']=='withouturl'){
									if ($clientDetail['vProfileImage']) {
										$clientDetail['image_Url'] = $this->data['base_url'].'uploads/client/'.$clientDetail['iClientId'].'/'.$clientDetail['vProfileImage'];
									}else{
										$clientDetail['image_Url'] = $this->data['base_url'].'uploads/plash-holder.png';
									}
								}
								else {
									$clientDetail['image_Url'] = $this->data['base_url'].'uploads/plash-holder.png';
								}
								$clientDetail['vPassword']= $this->decrypt($clientDetail['vPassword']);
								$data['data']=$clientDetail;
								$data['msg'] = "Registered Successfully";
							}
							else {
								$data['msg'] = "Failure";   
							}
						}
					}else{
						$iClientId = $this->webservices_model->getClientId('client',$vEmail);
						if ($iClientId['iClientId'] == '') {
							
							$rider['biFBId'] = $this->input->post('biFBId');
							$fullname = explode(" ",$this->input->post('fullname'));
							$rider['vFirstName'] = $fullname[0];
							if ($fullname[1]) {
								$rider['vLastName'] = $fullname[1];
							}
							$rider['vEmail'] = $this->input->post('vEmail');
							$vPassword = rand_str(6);
							$rider['vPassword'] = encrypt($vPassword);
							/*$rider['iCardTypeId']=$this->input->post('iCardTypeId');
							$rider['vCreditcardNo']=$this->input->post('vCreditcardNo');
							$rider['iMonth']=$this->input->post('iMonth');
							$rider['iYear']=$this->input->post('iYear');
							$rider['iCvvNo']=$this->input->post('iCvvNo');*/
							$rider['vCountryMobileCode'] = '+1';
							$rider['iMobileNo'] = $this->input->post('iMobileNo');
							$rider['eStatus']="Active";
							$rider['dRegisterDate'] = date('Y-m-d');
							if($this->input->post('eImageType') == 'withurl'){
								$rider['vProfileImage'] = $this->input->post('vProfileImage');           
								$rider['eImageType'] = 'withurl';            
							}
							$ClientId = $this->webservices_model->save_data($rider,"client");
							$new_client['iClientId']=$ClientId;
							$new_client['vPromotionCode']=str_pad($new_client['iClientId'],6,"0",STR_PAD_LEFT);
							$result = $this->webservices_model->update_client_detail($new_client);
							if ($ClientId) {
								// Send Email
								$siteurl=$this->config->item('base_url');
								$MailFooter = $this->data['MAIL_FOOTER']; 
								$adminEmailId= $this->data['EMAIL_ADMIN'];
								$siteName = $this->data['SITE_NAME'];
								$FirstName=ucfirst($rider['vFirstName']);
								$LastName=ucfirst($rider['vLastName']);
								$name1 =$FirstName.' '.$LastName.',';
								$name=ucfirst($name1);
								$bodyArr = array("#NAME#","#PASSWORD#","#EMAIL#","#SITEURL#","#MAILFOOTER#","#SITE_NAME#","#FIRSTNAME#","#LASTNAME#");
								$postArr = array($name,$vPassword,$vEmail,$siteurl,$MailFooter,$siteName,$link,$FirstName,$LastName);  
								$sendClient=$this->Send("NEW_RIDER_REGISTER","Client",$vEmail,$bodyArr,$postArr);
								$sendAdmin=$this->Send("ADMIN_NEW_RIDER_REGISTER","Admin",$adminEmailId,$bodyArr,$postArr);

								$clientDetail = $this->webservices_model->get_single_client_details_without_status($ClientId);
								$clientDetail['iClientId'] = "$ClientId";
								$clientDetail['fullname'] = $clientDetail['vFirstName'].' '.$clientDetail['vLastName'];         
								if(!empty($clientDetail)){

									if($clientDetail['eImageType']=='withurl'){
										$clientDetail['image_Url'] = $clientDetail['vProfileImage'];    
									}
									else if($clientDetail['eImageType']=='withouturl'){
										if ($clientDetail['vProfileImage']) {
											$clientDetail['image_Url'] = $this->data['base_url'].'uploads/client/'.$clientDetail['iClientId'].'/'.$clientDetail['vProfileImage'];
										}else{
											$clientDetail['image_Url'] = $this->data['base_url'].'uploads/plash-holder.png';
										}
									}
									else {
										$clientDetail['image_Url'] = $this->data['base_url'].'uploads/plash-holder.png';
									}
									$clientDetail['vPassword']= $this->decrypt($clientDetail['vPassword']);
									$data['data']=$clientDetail;
									$data['msg'] = "Registered Successfully";
								}
								else {
									$data['msg'] = "Failure";   
								}
							}
						}
					}
				}
			}else {
				$data['msg'] = "Failure";
			}
		}else {
			$data['msg'] = "Failure";
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function forgot_password(){
		$role=$this->input->post('role');
		$iMobileNo=trim($this->input->post('iMobileNo'));
		$email=trim($this->input->post('vEmail'));
		if($role=='rider'){
			$searchArr = array('iMobileNo' => $iMobileNo, 'vEmail' => $email);

			$getClientDetail=$this->webservices_model->getDetailForForgotPassword('client',$searchArr);
			if($getClientDetail){
				//update vForgotPasswordString
				$forgotPasswordOTP = random_string($type = 'numeric', 4);
				$update_rider['iClientId'] = $getClientDetail['iClientId'];
				$update_rider['vForgotPasswordString'] = $forgotPasswordOTP;
				$res = $this->webservices_model->update_client_detail($update_rider);
				// Send OTP
				$smsText = 'Your OneTouchCab Customer forgot password verification code is: '.$forgotPasswordOTP;
				// Send to test numbers
				require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
				$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
				$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
				$client = new Services_Twilio($AccountSid, $AuthToken);
				
				$extraChr = array(" ", "(", ")", "-", "[", "]");
				$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);
				try {
					$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
					$Data['msg'] = "Success";
				} catch (Exception $e) {
				    // echo 'Caught exception: ',  $e->getMessage(), "\n";
					$Data['msg'] = "SMS Sending Failure";
				}
				
			}else{
				$Data['msg'] = "User Not Exist";
			}
		}else if($role=='owner'){
			$searchArr = array('vMobileNo1' => $iMobileNo, 'vEmail' => $email);
			$getOwnerDetail=$this->webservices_model->getDetailForForgotPassword('vehicle_owner',$searchArr);
			if($getOwnerDetail){
				if($getOwnerDetail['eVerifiedMobile']=='Yes' && $getOwnerDetail['eApprovalStatus']=='Approved'){
					$forgotPasswordOTP = random_string($type = 'numeric', 4);
					$res = $this->webservices_model->update_owner_detail( array('vForgotPasswordString' => $forgotPasswordOTP),$getOwnerDetail['iVehicleOwnerId']);
					// Send OTP
					$smsText = 'Your OneTouchCab Vehicle Owner forgot password verification code is: '.$forgotPasswordOTP;
					// Send to test numbers
					require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
					$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
					$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
					$client = new Services_Twilio($AccountSid, $AuthToken);
					
					$extraChr = array(" ", "(", ")", "-", "[", "]");
					$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);
					try {
						$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
						$Data['msg'] = "Success";
					} catch (Exception $e) {
						$Data['msg'] = "SMS Sending Failure";
					}
					
				}else if ($getOwnerDetail['eVerifiedMobile']=='Yes' && $getOwnerDetail['eApprovalStatus']=='Pending'){
					$Data['msg'] = "Your account pending site admin approval. Please contact for further assistance.";
				}else if ($getOwnerDetail['eApprovalStatus']=='Rejected'){
					$Data['msg'] = "Your account is rejected by site admin. Please contact for further assistance.";
				}else{
					$Data['msg'] = "Your Account Is Inactive";
				}
			}else{
				$Data['msg'] = "User Not Exist";
			}
		}else if($role=='driver'){
			$searchArr = array('iMobileNo1' => $iMobileNo, 'vEmail' => $email);
			$getOwnerDetail=$this->webservices_model->getDetailForForgotPassword('driver',$searchArr);

			if(count($getOwnerDetail)>0){
				if($getOwnerDetail['eVerifiedMobile']=='Yes' && $getOwnerDetail['eStatus']=='Active'){
					$forgotPasswordOTP = random_string($type = 'numeric', 4);
					$res = $this->webservices_model->update_driver_detail( array('vForgotPasswordString' => $forgotPasswordOTP),$getOwnerDetail['iDriverId']);
					$smsText = 'Your OneTouchCab Vehicle Owner forgot password verification code is: '.$forgotPasswordOTP;
					require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
					$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
					$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
					$client = new Services_Twilio($AccountSid, $AuthToken);

					$extraChr = array(" ", "(", ")", "-", "[", "]");
					$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);

					try {
						$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
						$Data['msg'] = "Success";
					} catch (Exception $e) {
						$Data['msg'] = "SMS Sending Failure";
					}
					
				}else if($getOwnerDetail['eVerifiedMobile']=='No'){
					$Data['msg'] = "Your Mobile No is not varified";
				}else if ($getOwnerDetail['eStatus']=='Inactive'){
					$Data['msg'] = "Your Account Is Inactive";
				}
			}else{
				$Data['msg'] = "User Not Exist";
			}
		}else{
			$Data['msg'] = "Role Is Not Valid";
		}
		header('Content-type: application/json');
		$main = json_encode($Data);
		echo $main;exit; 
	}

	function resetPasswordWithOTP_22_11_2016(){
		$iOTP = trim($this->input->post('iOTP'));
		$role=$this->input->post('role');
		$vPassword = trim($this->input->post('vPassword'));
		
		if ($iOTP=='') {
			$Data['msg'] = "OTP Is Not Valid";
			header('Content-type: application/json');
			$callback = '';
			if (isset($_REQUEST['callback'])){
				$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
			}
			$main = json_encode($Data);
			echo $callback . ''.$main.'';
			exit;
		}
		if ($vPassword=='') {
			$Data['msg'] = "Failure";
			header('Content-type: application/json');
			$main = json_encode($Data);
			echo $main;
			exit;
		} else {
			$newPassword = encrypt($vPassword);
		}
		
		if($role=='rider'){
			$clientDetail = $this->webservices_model->getDetailByFieldValue('client','vForgotPasswordString',$iOTP);
			if ($clientDetail) {
				if($clientDetail['eStatus']=='Active' && $clientDetail['eVerifiedMobile']=='Yes'){
					$latitude = $this->input->post('latitude');
					$longitude = $this->input->post('longitude');
					if ($latitude=='' || $longitude=='') {
						$Data['msg'] = "Failure";
						header('Content-type: application/json');
						$main = json_encode($Data);
						echo $main;
						exit;
					}
					// Change status and varify, remove 
					$update_rider['iClientId'] = $clientDetail['iClientId'];
					$update_rider['vPassword'] = $newPassword;
					$update_rider['vForgotPasswordString'] = '';
					// $this->printthis($update_rider);
					// Update details
					$ClientId = $this->webservices_model->update_client_detail($update_rider);
					// Get latest details
					$getClientDetail = $this->webservices_model->getClientDetail($clientDetail['iClientId']);

					$base_path = $this->data['base_path'];
					$file_path = $base_path.'uploads/client/'.$getClientDetail['iClientId'].'/'.$getClientDetail['vProfileImage'];
						
					if($getClientDetail['eImageType']=='withurl'){
						$image_Url = $getClientDetail['vProfileImage'];
					}
					else if($getClientDetail['eImageType']=='withouturl'){
						if (file_exists($file_path)) {
							$image_Url = $base_upload.'client/'.$getClientDetail['iClientId'].'/'.$getClientDetail['vProfileImage'];
						}else{
							$image_Url = $base_upload.'plash-holder.png';
						}
					}else {
						$image_Url = $base_upload.'plash-holder.png';
					}
					
					$resData['iClientId'] = $getClientDetail['iClientId'];
					$resData['fullname'] = $getClientDetail['vFirstName'].' '.$getClientDetail['vLastName'];
					$resData['vFirstName'] = $getClientDetail['vFirstName'];
					$resData['vLastName'] = $getClientDetail['vLastName'];

					if(isset($getClientDetail['tAddress'])){
						$resData['tAddress'] = $getClientDetail['tAddress'];
					}else {
						$resData['tAddress'] = "";
					}
					
					$resData['vEmail'] = $getClientDetail['vEmail'];
					$resData['iMobileNo'] = $getClientDetail['iMobileNo'];
					$resData['eStatus'] = $getClientDetail['eStatus'];
					$resData['vProfileImage'] = $getClientDetail['vProfileImage'];
					$resData['vPassword'] = $getClientDetail['vPassword'];
					$resData['image_Url'] = $image_Url;
					$resData['vPromotionCode'] = ($getClientDetail['vPromotionCode'])?$getClientDetail['vPromotionCode']:"";
					$resData['vPostalCode'] = ($getClientDetail['vPostalCode'])?$getClientDetail['vPostalCode']:"";
					$resData['iCountryId'] = ($getClientDetail['iCountryId'])?$getClientDetail['iCountryId']:"";
					$resData['vCountry'] = ($getClientDetail['vCountry'])?$getClientDetail['vCountry']:"";
					$resData['iStateId'] = ($getClientDetail['iStateId'])?$getClientDetail['iStateId']:"";
					$resData['vState'] = ($getClientDetail['vState'])?$getClientDetail['vState']:"";
					$resData['iCityId'] = ($getClientDetail['iCityId'])?$getClientDetail['iCityId']:"";
					$resData['vCity'] = ($getClientDetail['vCity'])?$getClientDetail['vCity']:"";

					// Current country for United States,Canada Show only Taxi, Car Pool, shuttle else Show All
					if ($this->data['SHOWALLTYPE']=='Yes') {
						$resData['CurrentCountry']="ShowAll";
					} else {
						$source_country =$this->GetCountryFromAddressOrLatLong($latitude.','.$longitude,'latlong');
						$resData['CurrentCountry']=$source_country['long_name'];
					}
					/*echo "<pre>";print_r($resData);exit;*/
					$Data['data'] = $resData;
					$Data['msg']  = "Login Successfully";
					$Data['role'] = "rider";
				}else{
					$Data['msg'] = "Your Account Is Inactive";
				}
			} else {
				$Data['msg'] = "OTP Is Not Valid";
			}
		}else if($role=='owner'){
			//
			$ownerDetail = $this->webservices_model->getDetailByFieldValue('client','vForgotPasswordString',$iOTP);
			if($ownerDetail){

			}else{
				$Data['msg'] = "OTP Is Not Valid";
			}
		}else if($role=='driver'){
			//
		}else{   
			$Data['msg'] = "Role Is Not Valid";
		} 
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function resendRegistrationOTP(){
		$role=$this->input->post('role');
		$iMobileNo=trim($this->input->post('iMobileNo'));
		$email=trim($this->input->post('vEmail'));
		if($role=='rider'){
			$searchArr = array('iMobileNo' => $iMobileNo, 'vEmail' => $email);
			$getClientDetail=$this->webservices_model->getDetailForForgotPassword('client',$searchArr);
			if($getClientDetail){
				if ($getClientDetail['eVerifiedMobile']=='Yes'){
					if($getClientDetail['eStatus']=='Active'){
						$Data['msg'] = "Already Verified Mobile Number";
					}else{
						$Data['msg'] = "Your Account Is Freezed By Admin";
					}
				}else{
					$iOTP = random_string($type = 'numeric', 4);
					$update_rider['iClientId'] = $getClientDetail['iClientId'];
					$update_rider['iOTP'] = $iOTP;
					$res = $this->webservices_model->update_client_detail($update_rider);
					// Send OTP
					$smsText = 'Your OneTouchCab Customer verification code is: '.$iOTP;
					// Send to test numbers
					require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
					$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
					$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
					$client = new Services_Twilio($AccountSid, $AuthToken);
					// Change to $getClientDetail['iMobileNo'] when live
					$people = array( "+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => "");
					// $people = array( "+919722159392" => "","+919033376229" => "");
					// $people = array("+919722159392" => "");
					foreach ($people as $number => $name) { 
						try {
							$sms[] = $client->account->messages->sendMessage("+18562882821",$number,$smsText);
							$Data['msg'] = "Success";
						} catch (Exception $e) {
						    // echo 'Caught exception: ',  $e->getMessage(), "\n";
							$Data['msg'] = "SMS Sending Failure";
						}
					}
				}
			}else{
				$Data['msg'] = "User Not Exist";
			}
		}else if($role=='driver'){
			$Data['msg'] = "Success";
		}else if($role=='owner'){
			$searchArr = array('vMobileNo1' => $iMobileNo, 'vEmail' => $email);
			$getOwnerDetail=$this->webservices_model->getDetailForForgotPassword('vehicle_owner',$searchArr);
			if($getOwnerDetail){
				if ($getOwnerDetail['eVerifiedMobile']=='Yes'){
					if($getOwnerDetail['eApprovalStatus']=='Approved'){
						$Data['msg'] = "Already Verified Mobile Number";
					}else if($getOwnerDetail['eApprovalStatus']=='Pending'){
						$Data['msg'] = "Your account pending site admin approval. Please contact for further assistance.";
					}else if($getOwnerDetail['eApprovalStatus']=='Rejected'){
						$Data['msg'] = "Your account is rejected by site admin. Please contact for further assistance.";
					}
				}else{
					$iOTP = random_string($type = 'numeric', 4);

					$res = $this->webservices_model->update_owner_detail( array('iOTP' => $iOTP),$getOwnerDetail['iVehicleOwnerId']);
					// Send OTP
					$smsText = 'Your OneTouchCab Vehicle Owner verification code is: '.$iOTP;
					// Send to test numbers
					require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
					$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
					$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
					$client = new Services_Twilio($AccountSid, $AuthToken);
					// Change to $getOwnerDetail['vMobileNo1'] when live
					$people = array( "+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => "");
					// $people = array( "+919722159392" => "","+919033376229" => "");
					// $people = array("+919722159392" => "");
					foreach ($people as $number => $name) { 
						try {
							$sms[] = $client->account->messages->sendMessage("+18562882821",$number,$smsText);
							$Data['msg'] = "Success";
						} catch (Exception $e) {
						    // echo 'Caught exception: ',  $e->getMessage(), "\n";
							$Data['msg'] = "SMS Sending Failure";
						}
					}
				}
			}else{
				$Data['msg'] = "User Not Exist";
			}
		}else{
			$Data['msg'] = "Role Is Not Valid";
		}
		header('Content-type: application/json');
		$main = json_encode($Data);
		echo $main;exit;
	}

	function OwnerRegister(){
		$vEmail = $this->input->post('vEmail');
		if($vEmail != '' && $this->input->post('vFirstName')!='' && $this->input->post('vLastName')!='' && $this->input->post('tBusinessName')!='' && $this->input->post('eBusinessType')!='' && $this->input->post('vPassword')!='' && $this->input->post('vMobileNo1')!=''){
			// Check Email
			$chk_vo_email = $this->webservices_model->check_email_exists('vehicle_owner',$vEmail);
			$chk_cl_email = $this->webservices_model->check_email_exists("client",$vEmail);
			$chk_dr_email = $this->webservices_model->check_email_exists("driver",$vEmail);
			
			if($chk_dr_email > 0 || $chk_cl_email > 0 || $chk_vo_email > 0){
				$data['msg'] = "Email Address Exist";
				header('Content-type: application/json');
				$main = json_encode($data);
				echo $main;
				exit; 
			}
			// Check Mobile
			$iMobileNo = $this->input->post('vMobileNo1');
			/*$extraChr = array(" ", "(", ")", "-", "[", "]");*/
			/*$iMobileNo = str_replace($extraChr, "", $iMobileNo);*/
			
			// $chk_cl_mobile = $this->webservices_model->getDetailByFieldValue('client','iMobileNo',$iMobileNo);
			
			$chk_vo_mobile1 = $this->webservices_model->getDetailByFieldValue('vehicle_owner','vMobileNo1',$iMobileNo);
			$chk_vo_mobile2 = $this->webservices_model->getDetailByFieldValue('vehicle_owner','vMobileNo2',$iMobileNo);
			$chk_vo_mobile3 = $this->webservices_model->getDetailByFieldValue('vehicle_owner','vWorkPhoneNo1',$iMobileNo);
			$chk_vo_mobile4 = $this->webservices_model->getDetailByFieldValue('vehicle_owner','vWorkPhoneNo2',$iMobileNo);
			$chk_dr_mobile1 = $this->webservices_model->getDetailByFieldValue("driver",'iMobileNo1',$iMobileNo);
			$chk_dr_mobile2 = $this->webservices_model->getDetailByFieldValue("driver",'iMobileNo2',$iMobileNo);
			$chk_dr_mobile3 = $this->webservices_model->getDetailByFieldValue("driver",'iTelephone1',$iMobileNo);
			$chk_dr_mobile4 = $this->webservices_model->getDetailByFieldValue("driver",'iTelephone2',$iMobileNo);
			
			if ($chk_vo_mobile1 || $chk_vo_mobile2 || $chk_vo_mobile3 || $chk_vo_mobile4 || $chk_dr_mobile1 || $chk_dr_mobile2 || $chk_dr_mobile3 || $chk_dr_mobile4 ){
				
				$data['msg'] = "Mobile Number Exist";
				header('Content-type: application/json');
				$main = json_encode($data);
				echo $main;
				exit; 
			}

			$ownerData['vFirstName']=$this->input->post('vFirstName');
			$ownerData['vLastName']=$this->input->post('vLastName');
			$ownerData['tBusinessName']=$this->input->post('tBusinessName');

			$typeArr = array('Individual','Partnership','Company','Other');
			if (in_array($this->input->post('eBusinessType'), $typeArr)) {
				if ($this->input->post('eBusinessType')=='Other') {
					if ($this->input->post('vOtherBusinessType')!='') {
						$ownerData['eBusinessType']=$this->input->post('eBusinessType');
						$ownerData['vOtherBusinessType']=$this->input->post('vOtherBusinessType');
					} else {
						$data['msg'] = "Error";
						header('Content-type: application/json');
						$callback = '';
						if (isset($_REQUEST['callback'])){
							$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
						}
						$main = json_encode($data);
						echo $callback . ''.$main.'';
						exit; 
					}
				} else {
					$ownerData['eBusinessType']=$this->input->post('eBusinessType');
				}
			} else {
				$data['msg'] = "Error";
				header('Content-type: application/json');
				$main = json_encode($data);
				echo $main;
				exit; 
			}
			//--------------------------------------------------------
			$ownerData['vMobileNo1']=$iMobileNo;
			$ownerData['vEmail']=$vEmail;
			$password = $this->input->post('vPassword');
			$ownerData['vPassword']=encrypt($password);
			if($this->input->post('vPostalCode') != ''){
				$ownerData['vPostalCode']=$this->input->post('vPostalCode');
			}else{
				$ownerData['vPostalCode']='';
			}
			$ownerData['eStatus']='Inactive';
			$ownerData['vActivationCode']=rand_str();
			$ownerData['iOTP']=random_string($type = 'numeric', 4);
			$ownerData['dRegisterDate'] = date('Y-m-d');
			$iVehicleOwnerId = $this->webservices_model->save_data($ownerData,'vehicle_owner');
			if ($iVehicleOwnerId) {
				if($_FILES['vProfile']['name']!=''){
					$size=array();
					$size['width']='57';
					$size['height']='57';
					$size['width2']='228';
					$size['height2']='228';
					$image_uploaded =$this->do_upload_img($iVehicleOwnerId,'vehicle_owner','vProfile',$size);
					$user['vProfile'] = $image_uploaded ;
					$ownerImage = $this->webservices_model->update_owner_detail($user,$iVehicleOwnerId);
				}
			
				// Send Email
				$siteurl = $this->config->item('base_url');
				$MailFooter = $this->data['MAIL_FOOTER'];
				$adminEmailId = $this->data['EMAIL_ADMIN'];
				$siteName = $this->data['SITE_NAME'];
				$name = ucfirst($ownerData['vFirstName']). ' ' . ucfirst($ownerData['vLastName']);
				$image = $siteurl;
				$link=$this->data['site_url'].'login/confirm_email?code='.$ownerData['vActivationCode']."&role=vehicle_owner";
				$bodyArr = array("#NAME#", "#PASSWORD#", "#EMAIL#", "#SITEURL#", "#MAILFOOTER#", "#SITE_NAME#", "#FIRSTNAME#", "#LASTNAME#", "#IMAGE_URL#","#LINK#");
				$postArr = array($name, $password, $vEmail, $siteurl, $MailFooter, $siteName, ucfirst($ownerData['vFirstName']), ucfirst($ownerData['vLastName']), $image,$link);
				$sendClient = $this->Send("NEW_OWNER_REGISTER", "Client", $vEmail, $bodyArr, $postArr);
				$smsText = 'Your OneTouchCab Vehicle Owner verification code is: '.$ownerData['iOTP'];
				require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
				$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
				$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
				$client = new Services_Twilio($AccountSid, $AuthToken);
				
				$extraChr = array(" ", "(", ")", "-", "[", "]");
				$update_rider_no = '+'.str_replace($extraChr, "", $iMobileNo);
				try {
					$sms[] = $client->account->messages->sendMessage("+18562882821",$update_rider_no,$smsText);
					$msg= "New Owner (".$name.") Has been Registerd to site please login with your details and check";
            		$this->SendEmailNotificationToAdmin($msg);
					$data['msg'] = "Registered Successfully";
				} catch (Exception $e) {
				    // echo 'Caught exception: ',  $e->getMessage(), "\n";
					$data['msg'] = "SMS Sending Failure";
				}
				

				// as Driver
				//---------------------------------------
				$driverData['iVehicleOwnerId']=$iVehicleOwnerId;
				$driverData['vFirstName']=$ownerData['vFirstName'];
				$driverData['vLastName']=$ownerData['vLastName'];
				$driverData['vEmail']=$vEmail;
				$driverData['vBusinessName']=$ownerData['vFirstName'];
				$driverData['iMobileNo1']=$ownerData['vMobileNo1'];
				$driverData['tAddress']="";
				$driverData['iCountryId']="";
				$driverData['iStateId']="";
				$driverData['iCityId']="";
				$driverData['vZipcode']="";
				$driverData['eAvailability']='CarPoolBoth';
				$driverData['eSmokingPreference']='Does Not Matter';
				$driverData['eGenderPreference']='Does Not Matter';
				$driverData['dRegisterDate'] = date('Y-m-d');
				$driverData['vActivationCode'] = random_string('alnum',8);
				$driverData['iOTP'] = random_string('numeric', 4);
				$driverData['eStatus'] = 'Inactive';
				$driverPassword=$password;
				$driverData['vPassword'] = $this->encrypt($driverPassword);
				$iDriverId = $this->webservices_model->save_data($driverData,'driver');
				$extraChr = array(" ", "(", ")", "-", "[", "]");
				$update_driver_no = '+'.str_replace($extraChr, "", $ownerData['vMobileNo1']);
				if ($_FILES['vProfile']['name'] != '') {
					$clean_name = $this->clean($_FILES['vProfile']['name']);
					$img_uploaded = $this->do_upload_driver_profile_photo($iDriverId,$clean_name,'vProfileImage');
					$res = $this->webservices_model->update_driver_detail(array('vProfileImage' => $img_uploaded),$iDriverId);
				}
				// Send Mail
				$driverlink=$this->data['site_url'].'login/confirm_email?code='.$driverData['vActivationCode'];
				$driverBodyArr = array("#NAME#", "#PASSWORD#", "#EMAIL#", "#SITEURL#", "#MAILFOOTER#", "#SITE_NAME#", "#LINK#", "#FIRSTNAME#", "#LASTNAME#", "#IMAGE_URL#");
				$driverPostArr = array($name, $password, $vEmail, $siteurl, $MailFooter, $siteName, $driverlink, ucfirst($ownerData['vFirstName']), ucfirst($ownerData['vLastName']), $image);
				$sendDriver = $this->Send("CREATE_DRIVER_BY_OWNER", "Driver", $vEmail, $driverBodyArr, $driverPostArr);
				// OTP sms to Driver
				$smsTextDriver = 'Your OneTouchCab Driver verification code is: '.$driverData['iOTP'];
				$client = new Services_Twilio($AccountSid, $AuthToken);
				// $people = array( "+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => "" );
				// $people = array( "+919722159392" => "","+919033376229" => "");
				// $people = array( "+919722159392" => "");
				// foreach ($people as $number => $name) { 
					// try {
					// 	$sms[] = $client->account->messages->sendMessage("+18562882821",$update_driver_no,$smsTextDriver);
					// 	$data['msg'] = "Registered Successfully";
					// } catch (Exception $e) {
					//     $data['msg'] = "SMS Sending Failure";
					// }
				//}
				$data['iVehicleOwnerId']="$iVehicleOwnerId";
			}else{
				$data['msg'] = "Registeration Error";
			}
		}else{
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$main = json_encode($data);
		echo $main;
		exit;
	}

	function SendEmailNotificationToAdmin($msg){

        $admins=$this->webservices_model->getAllAdmin();
        foreach ($admins as $key => $value){
            $siteurl = $this->config->item('base_url');
            $MailFooter = $this->data['MAIL_FOOTER'];
            $adminEmailId = $this->data['EMAIL_ADMIN'];
            $siteName = $this->data['SITE_NAME'];
            $image = $siteurl;
            $bodyArr = array("#MSG#","#SITEURL#", "#MAILFOOTER#", "#SITE_NAME#", "#IMAGE_URL#");
            $postArr = array($msg, $siteurl, $MailFooter, $siteName, $image);
            $sendEmail = $this->Send("ADMIN_INFO", "Client", $value['vEmail'], $bodyArr, $postArr);
		}
    }

	function resendOTP(){
		$role=$this->input->post('role');
		$type=$this->input->post('type');
		$iMobileNo=trim($this->input->post('iMobileNo'));
		$email=trim($this->input->post('vEmail'));
		if($role=='rider'){
			$searchArr = array('iMobileNo' => $iMobileNo, 'vEmail' => $email);
			$getClientDetail=$this->webservices_model->getDetailForForgotPassword('client',$searchArr);
			if($getClientDetail){
				if($type=='Registration'){
					if ($getClientDetail['eVerifiedMobile']=='Yes'){
						if($getClientDetail['eStatus']=='Active'){
							$Data['msg'] = "Already Verified Mobile Number";
						}else{
							$Data['msg'] = "Your Account Is Freezed By Admin";
						}
					}else{
						$iOTP = random_string($type = 'numeric', 4);
						$update_rider['iClientId'] = $getClientDetail['iClientId'];
						$update_rider['iOTP'] = $iOTP;
						$res = $this->webservices_model->update_client_detail($update_rider);
						// Send OTP
						$smsText = 'Your OneTouchCab Customer verification code is: '.$iOTP;
						// Send to test numbers
						require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
						$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
						$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
						$client = new Services_Twilio($AccountSid, $AuthToken);
						// Change to $getClientDetail['iMobileNo'] when live
						/*$people = array( "+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => "");*/
						// $people = array( "+919722159392" => "","+919033376229" => "");
						// $people = array("+919722159392" => "");
						$extraChr = array(" ", "(", ")", "-", "[", "]");
						$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);
						/*foreach ($people as $number => $name) { */
							try {
								$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
								$Data['msg'] = "Success";
							} catch (Exception $e) {
							    // echo 'Caught exception: ',  $e->getMessage(), "\n";
								$Data['msg'] = "SMS Sending Failure";
							}
						/*}*/
					}
				}else if($type=='ForgotPassword'){
					if ($getClientDetail['eVerifiedMobile']=='Yes' && $getClientDetail['eStatus']=='Active'){
						$forgotPasswordOTP = random_string($type = 'numeric', 4);
						$update_rider['iClientId'] = $getClientDetail['iClientId'];
						$update_rider['vForgotPasswordString'] = $forgotPasswordOTP;
						$res = $this->webservices_model->update_client_detail($update_rider);
						// Send OTP
						$smsText = 'Your OneTouchCab Customer forgot password verification code is: '.$forgotPasswordOTP;
						// Send to test numbers
						require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
						$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
						$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
						$client = new Services_Twilio($AccountSid, $AuthToken);
						//$people = array( "+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => "");
						/*foreach ($people as $number => $name) { */
						$extraChr = array(" ", "(", ")", "-", "[", "]");
						$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);
							try {
								$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
								$Data['msg'] = "Success";
							} catch (Exception $e) {
							   $Data['msg'] = "SMS Sending Failure";
							}
						/*}*/
					}else if ($getClientDetail['eVerifiedMobile']=='Yes' && $getClientDetail['eStatus']=='Inactive'){
						$Data['msg'] = "Your Account Is Freezed By Admin";
					}else{
						$Data['msg'] = "Your Account Is Inactive";
					}
				}else{
					$Data['msg'] = "Type Is Not Valid";
				}
				
			}else{
				$Data['msg'] = "User Not Exist";
			}
		}else if($role=='driver'){
			if($type=='Registration'){
				$searchArr = array('iMobileNo1' => $iMobileNo, 'vEmail' => $email);
				$getDriverDetail=$this->webservices_model->getDetailForForgotPassword('driver',$searchArr);
				if(count($getDriverDetail)>0){
					if ($getDriverDetail['eStatus']=='Active'){
						$OTP = random_string($type = 'numeric', 4);
						$update_driver['iOTP'] = $OTP;
						$res = $this->webservices_model->update_driver_detail($update_driver,$getDriverDetail['iDriverId']);
						$smsText = 'Your OneTouchCab Driver verification code is: '.$OTP;
						require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
						$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
						$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
						$client = new Services_Twilio($AccountSid, $AuthToken);
						$extraChr = array(" ", "(", ")", "-", "[", "]");
						$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);
						try {
							$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
							$Data['msg'] = "Success";
						} catch (Exception $e) {
						    $Data['msg'] = "SMS Sending Failure";
						}
					}else{
						$Data['msg'] = "Your Account Is Inactive";
					}
				}else{
					$Data['msg'] = "User Not Exist";
				}

			}else if($type=='ForgotPassword'){
				$searchArr = array('iMobileNo1' => $iMobileNo, 'vEmail' => $email);
				$getDriverDetail=$this->webservices_model->getDetailForForgotPassword('driver',$searchArr);
				if(count($getDriverDetail)>0){
					if ($getDriverDetail['eVerifiedMobile']=='Yes' && $getDriverDetail['eStatus']=='Active'){
						$forgotPasswordOTP = random_string($type = 'numeric', 4);
						$update_driver['vForgotPasswordString'] = $forgotPasswordOTP;
						$res = $this->webservices_model->update_driver_detail($update_driver,$getDriverDetail['iDriverId']);
						$smsText = 'Your OneTouchCab Customer forgot password verification code is: '.$forgotPasswordOTP;
						require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
						$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
						$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
						$client = new Services_Twilio($AccountSid, $AuthToken);
						/*$people = array( "+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => "");*/
						/*foreach ($people as $number => $name) { */
						$extraChr = array(" ", "(", ")", "-", "[", "]");
						$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);
							try {
								$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
								$Data['msg'] = "Success";
							} catch (Exception $e) {
							    $Data['msg'] = "SMS Sending Failure";
							}
						/*}*/
					}else if ($getDriverDetail['eVerifiedMobile']=='No'){
						$Data['msg'] = "Your Mobile No is not varified";
					}else{
						$Data['msg'] = "Your Account Is Inactive";
					}
				}else{
					$Data['msg'] = "User Not Exist";
				}	
			}else{
				$Data['msg'] = "Type Is Not Valid";
			}
		}else if($role=='owner'){
			$searchArr = array('vMobileNo1' => $iMobileNo, 'vEmail' => $email);
			$getOwnerDetail=$this->webservices_model->getDetailForForgotPassword('vehicle_owner',$searchArr);
			if($getOwnerDetail){
				if($type=='Registration'){
					if ($getOwnerDetail['eVerifiedMobile']=='Yes'){
						if($getOwnerDetail['eApprovalStatus']=='Approved'){
							$Data['msg'] = "Already Verified Mobile Number";
						}else if($getOwnerDetail['eApprovalStatus']=='Pending'){
							$Data['msg'] = "Your account pending site admin approval. Please contact for further assistance.";
						}else if($getOwnerDetail['eApprovalStatus']=='Rejected'){
							$Data['msg'] = "Your account is rejected by site admin. Please contact for further assistance.";
						}
					}else{
						$iOTP = random_string($type = 'numeric', 4);

						$res = $this->webservices_model->update_owner_detail( array('iOTP' => $iOTP),$getOwnerDetail['iVehicleOwnerId']);
						// Send OTP
						$smsText = 'Your OneTouchCab Vehicle Owner verification code is: '.$iOTP;
						// Send to test numbers
						require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
						$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
						$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
						$client = new Services_Twilio($AccountSid, $AuthToken);
						$people = array( "+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => "");
						$extraChr = array(" ", "(", ")", "-", "[", "]");
						$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);
						/*foreach ($people as $number => $name) { */
							try {
								$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
								$Data['msg'] = "Success";
							} catch (Exception $e) {
							    // echo 'Caught exception: ',  $e->getMessage(), "\n";
								$Data['msg'] = "SMS Sending Failure";
							}
						/*}*/
					}
				}else if($type=='ForgotPassword'){
					if($getOwnerDetail['eVerifiedMobile']=='Yes' && $getOwnerDetail['eApprovalStatus']=='Approved'){
						$forgotPasswordOTP = random_string($type = 'numeric', 4);
						$res = $this->webservices_model->update_owner_detail( array('vForgotPasswordString' => $forgotPasswordOTP),$getOwnerDetail['iVehicleOwnerId']);
						
						// Send OTP
						$smsText = 'Your OneTouchCab Vehicle Owner forgot password verification code is: '.$forgotPasswordOTP;
						// Send to test numbers
						require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
						$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
						$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
						$client = new Services_Twilio($AccountSid, $AuthToken);
						$people = array( "+919722159392" => "","+919033376229" => "","+917984885065" => "","+12672165412" => "");
						$extraChr = array(" ", "(", ")", "-", "[", "]");
						$iMobileNo = '+'.str_replace($extraChr, "", $iMobileNo);
						/*foreach ($people as $number => $name) { */
							try {
								$sms[] = $client->account->messages->sendMessage("+18562882821",$iMobileNo,$smsText);
								$Data['msg'] = "Success";
							} catch (Exception $e) {
								$Data['msg'] = "SMS Sending Failure";
							}
						/*}*/
					}else if ($getOwnerDetail['eVerifiedMobile']=='Yes' && $getOwnerDetail['eApprovalStatus']=='Pending'){
						$Data['msg'] = "Your account pending site admin approval. Please contact for further assistance.";
					}else if ($getOwnerDetail['eApprovalStatus']=='Rejected'){
						$Data['msg'] = "Your account is rejected by site admin. Please contact for further assistance.";
					}else{
						$Data['msg'] = "Your Account Is Inactive";
					}
				}else{
					$Data['msg'] = "Type Is Not Valid";
				}
			}else{
				$Data['msg'] = "User Not Exist";
			}
		}else{
			$Data['msg'] = "Role Is Not Valid";
		}
		header('Content-type: application/json');
		$main = json_encode($Data);
		echo $main;exit;
	}

	function vehicalOwnerLogin($vEmail,$vPassword){
		$base_upload=$this->data['base_upload'];
		$base_path = $this->data['base_path'];
		if(($vEmail != '') && ($vPassword != '')){
			$owneremail = $this->webservices_model->check_Exist('vEmail',$vEmail,'vehicle_owner');
			if($owneremail){
				$ownerpassword = $this->webservices_model->check_password($vEmail,$vPassword,'vehicle_owner');
				if($ownerpassword){
					$ownerdetail = $this->webservices_model->check_owner_auth($vEmail,$vPassword);
					if($ownerdetail['eVerifiedMobile']=='Yes' && $ownerdetail['eApprovalStatus']=='Approved' && $ownerdetail['eStatus']=='Active'){
						$file_path = $base_path.'uploads/vehicle_owner/'.$ownerdetail['iVehicleOwnerId'].'/'.$ownerdetail['vProfile'];
						if (file_exists($file_path)) {
							$image_Url = $base_upload.'vehicle_owner/'.$ownerdetail['iVehicleOwnerId'].'/'.$ownerdetail['vProfile'];
						}else{
							$image_Url = $base_upload.'plash-holder.png';
						}
						$ownerData['iVehicleOwnerId'] = $ownerdetail['iVehicleOwnerId'];
						$ownerData['vFirstName'] = $ownerdetail['vFirstName'];
						$ownerData['vLastName'] = $ownerdetail['vLastName'];
						$ownerData['fullname'] = $ownerdetail['vFirstName'].' '.$ownerdetail['vLastName'];
						$ownerData['vEmail'] = $ownerdetail['vEmail'];
						$ownerData['vMobileNo'] = $ownerdetail['vMobileNo'];
						$ownerData['eStatus'] = $ownerdetail['eStatus'];
						$ownerData['vProfile'] = $ownerdetail['vProfile'];
						$ownerData['image_Url'] = $image_Url;
						$ownerData['tBusinessName'] = $ownerdetail['tBusinessName'];
						$ownerData['eBusinessType'] = $ownerdetail['eBusinessType'];
						$ownerData['vOtherBusinessType'] = $ownerdetail['vOtherBusinessType'];
						$ownerData['tAddress'] = ($ownerdetail['tAddress']=='')?'':$ownerdetail['tAddress'];
						$ownerData['iCountryId'] = ($ownerdetail['iCountryId'])?$ownerdetail['iCountryId']:"";
						$ownerData['vCountry'] = ($ownerdetail['vCountry'])?$ownerdetail['vCountry']:"";
						$ownerData['iStateId'] = ($ownerdetail['iStateId'])?$ownerdetail['iStateId']:"";
						$ownerData['vState'] = ($ownerdetail['vState'])?$ownerdetail['vState']:"";
						$ownerData['iCityId'] = ($ownerdetail['iCityId'])?$ownerdetail['iCityId']:"";
						$ownerData['vCity'] = ($ownerdetail['vCity'])?$ownerdetail['vCity']:"";
						$ownerData['vPostalCode'] = ($ownerdetail['vPostalCode'])?$ownerdetail['vPostalCode']:"";
						$ownerData['eOutOfService'] = $ownerdetail['eOutOfService'];
						$Data['data'] = $ownerData;
						$Data['msg']  = "Login Successfully";
						$Data['role'] = "owner";
					}else if($ownerdetail['eVerifiedMobile']=='Yes' && $ownerdetail['eApprovalStatus']=='Pending' && $ownerdetail['eStatus']=='Active'){
						$Data['msg'] = "Your account pending site admin approval. Please contact for further assistance.";
					}else if($ownerdetail['eApprovalStatus']=='Rejected'){
						$Data['msg'] = "Your account is rejected by site admin. Please contact for further assistance.";
					}else if($ownerdetail['eVerifiedMobile']=='No'){
						$Data['msg'] = "Mobile Number Is Not Verified.";
						$Data['iMobileNo'] = $ownerdetail['vMobileNo1'];
					}else if($ownerdetail['eStatus']=='Inactive'){
						$Data['msg'] = "Your Account Is Freezed By Admin.";
					}else{
						$Data['msg'] = "Your Account Is Freezed By Admin.";
					}
				}else{
					$Data['msg'] = "Your password doesn't match.";
				}
			}else{
				$Data['msg'] = "Your email doesn't match.";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function riderLogin($vEmail,$vPassword,$latitude,$longitude){
		$base_upload=$this->data['base_upload'];
		if(($vEmail != '') && ($vPassword != '')){
			$clientemail = $this->webservices_model->check_Exist('vEmail',$vEmail,'client');
			if($clientemail){
				$clientpassword = $this->webservices_model->check_password($vEmail,$vPassword,'client');
				if($clientpassword){
					$clientDetail = $this->webservices_model->check_client_auth($vEmail,$vPassword);
					// $this->printthis($clientDetail);
					if($clientDetail){
						if($clientDetail['eStatus'] == 'Active' && $clientDetail['eVerifiedMobile'] == 'Yes'){
							# Login
							$base_path = $this->data['base_path'];
							$file_path = $base_path.'uploads/client/'.$clientDetail['iClientId'].'/'.$clientDetail['vProfileImage'];
							if($clientDetail['eImageType']=='withurl'){
								$clientDetail['image_Url'] = $clientDetail['vProfileImage'];                    
							}else if($clientDetail['eImageType']=='withouturl'){
								if (file_exists($file_path)) {
									$clientDetail['image_Url'] = $base_upload.'client/'.$clientDetail['iClientId'].'/'.$clientDetail['vProfileImage'];
								}else{
									$clientDetail['image_Url'] = $base_upload.'plash-holder.png';
								}
							}else {
								$clientDetail['image_Url'] = $base_upload.'plash-holder.png';
							}
							$clientDetails['iClientId'] = $clientDetail['iClientId'];
							$getClientDetail = $this->webservices_model->getClientDetailbyId($clientDetails['iClientId']);
							/*echo "<pre>";print_r($getClientDetail);exit;*/
							$clientDetails['fullname'] = $clientDetail['vFirstName'].' '.$clientDetail['vLastName'];
							if ($clientDetail['vFirstName'] && $clientDetail['vLastName']) {
								$clientDetails['vFirstName'] = $clientDetail['vFirstName'];
								$clientDetails['vLastName'] = $clientDetail['vLastName'];
							}
							if(isset($clientDetail['tAddress'])){
								$clientDetails['tAddress'] = $clientDetail['tAddress'];
							}else{
								$clientDetails['tAddress'] = "";
							}
							$clientDetails['vEmail'] = $clientDetail['vEmail'];
							$clientDetails['iMobileNo'] = $clientDetail['iMobileNo'];
							$clientDetails['eStatus'] = $clientDetail['eStatus'];
							$clientDetails['eGender'] = $clientDetail['eGender'];
							$clientDetails['vProfileImage'] = $clientDetail['vProfileImage'];
							$clientDetails['vPassword'] = $clientDetail['vPassword'];
							$clientDetails['image_Url'] = $clientDetail['image_Url'];
							$clientDetails['vPromotionCode'] = ($clientDetail['vPromotionCode'])?$clientDetail['vPromotionCode']:"";
							$clientDetails['vPostalCode'] = ($getClientDetail['vPostalCode'])?$getClientDetail['vPostalCode']:"";
							$clientDetails['iCountryId'] = ($getClientDetail['iCountryId'])?$getClientDetail['iCountryId']:"";
							$clientDetails['vCountry'] = ($getClientDetail['vCountry'])?$getClientDetail['vCountry']:"";
							$clientDetails['iStateId'] = ($getClientDetail['iStateId'])?$getClientDetail['iStateId']:"";
							$clientDetails['vState'] = ($getClientDetail['vState'])?$getClientDetail['vState']:"";
							$clientDetails['iCityId'] = ($getClientDetail['iCityId'])?$getClientDetail['iCityId']:"";
							$clientDetails['vCity'] = ($getClientDetail['vCity'])?$getClientDetail['vCity']:"";
							// Current country for United States,Canada Show only Taxi, Car Pool, shuttle else Show All
							if ($this->data['SHOWALLTYPE']=='Yes') {
								$clientDetails['CurrentCountry']="ShowAll";
							} else {
								$source_country =$this->GetCountryFromAddressOrLatLong($latitude.','.$longitude,'latlong');
								$clientDetails['CurrentCountry']=$source_country['long_name'];
							}
							/*echo "<pre>";print_r($clientDetails);exit;*/
							$Data['data'] = $clientDetails;
							$Data['msg']  = "Login Successfully";
							$Data['role'] = "rider";
						}else if($clientDetail['eStatus'] == 'Inactive' && $clientDetail['eVerifiedMobile'] == 'Yes'){
					  		$Data['msg'] = "Your Account Is Freezed By Admin.";
						}else{
					  		$Data['msg'] = "Mobile Number Is Not Verified.";
					  		$Data['iMobileNo'] = $clientDetail['iMobileNo'];
						}
					}else{
					  $Data['msg'] = "No Record Found";
					}
				}else{
					$Data['msg'] = "Your password doesn't match.";
				}
			}else{
				$Data['msg'] = "Your email doesn't match.";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$main = json_encode($Data);
		echo $main;
		exit;
	}

	function resetPasswordWithOTP(){
		$iOTP = trim($this->input->post('iOTP'));
		$role=$this->input->post('role');
		$vPassword = trim($this->input->post('vPassword'));
		
		if ($iOTP=='') {
			$Data['msg'] = "OTP Is Not Valid";
			header('Content-type: application/json');
			$callback = '';
			if (isset($_REQUEST['callback'])){
				$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
			}
			$main = json_encode($Data);
			echo $callback . ''.$main.'';
			exit;
		}
		if ($vPassword=='') {
			$Data['msg'] = "Failure";
			header('Content-type: application/json');
			$main = json_encode($Data);
			echo $main;
			exit;
		} else {
			$newPassword = encrypt($vPassword);
		}
		
		if($role=='rider'){
			$clientDetail = $this->webservices_model->getDetailByFieldValue('client','vForgotPasswordString',$iOTP);
			if ($clientDetail) {
				if($clientDetail['eStatus']=='Active' && $clientDetail['eVerifiedMobile']=='Yes'){
					$latitude = $this->input->post('latitude');
					$longitude = $this->input->post('longitude');
					if ($latitude=='' || $longitude=='') {
						$Data['msg'] = "Failure";
						header('Content-type: application/json');
						$main = json_encode($Data);
						echo $main;
						exit;
					}
					// Change password and remove otp
					$update_rider['iClientId'] = $clientDetail['iClientId'];
					$update_rider['vPassword'] = $newPassword;
					$update_rider['vForgotPasswordString'] = '';
					// $this->printthis($update_rider);
					// Update details
					$ClientId = $this->webservices_model->update_client_detail($update_rider);
					// Get latest details
					$getClientDetail = $this->webservices_model->getClientDetail($clientDetail['iClientId']);

					$base_path = $this->data['base_path'];
					$file_path = $base_path.'uploads/client/'.$getClientDetail['iClientId'].'/'.$getClientDetail['vProfileImage'];
						
					if($getClientDetail['eImageType']=='withurl'){
						$image_Url = $getClientDetail['vProfileImage'];
					}else if($getClientDetail['eImageType']=='withouturl'){
						if (file_exists($file_path)) {
							$image_Url = $base_upload.'client/'.$getClientDetail['iClientId'].'/'.$getClientDetail['vProfileImage'];
						}else{
							$image_Url = $base_upload.'plash-holder.png';
						}
					}else {
						$image_Url = $base_upload.'plash-holder.png';
					}
					
					$resData['iClientId'] = $getClientDetail['iClientId'];
					$resData['fullname'] = $getClientDetail['vFirstName'].' '.$getClientDetail['vLastName'];
					$resData['vFirstName'] = $getClientDetail['vFirstName'];
					$resData['vLastName'] = $getClientDetail['vLastName'];

					if(isset($getClientDetail['tAddress'])){
						$resData['tAddress'] = $getClientDetail['tAddress'];
					}else {
						$resData['tAddress'] = "";
					}
					
					$resData['vEmail'] = $getClientDetail['vEmail'];
					$resData['iMobileNo'] = $getClientDetail['iMobileNo'];
					$resData['eStatus'] = $getClientDetail['eStatus'];
					$resData['vProfileImage'] = $getClientDetail['vProfileImage'];
					$resData['vPassword'] = $getClientDetail['vPassword'];
					$resData['image_Url'] = $image_Url;
					$resData['vPromotionCode'] = ($getClientDetail['vPromotionCode'])?$getClientDetail['vPromotionCode']:"";
					$resData['vPostalCode'] = ($getClientDetail['vPostalCode'])?$getClientDetail['vPostalCode']:"";
					$resData['iCountryId'] = ($getClientDetail['iCountryId'])?$getClientDetail['iCountryId']:"";
					$resData['vCountry'] = ($getClientDetail['vCountry'])?$getClientDetail['vCountry']:"";
					$resData['iStateId'] = ($getClientDetail['iStateId'])?$getClientDetail['iStateId']:"";
					$resData['vState'] = ($getClientDetail['vState'])?$getClientDetail['vState']:"";
					$resData['iCityId'] = ($getClientDetail['iCityId'])?$getClientDetail['iCityId']:"";
					$resData['vCity'] = ($getClientDetail['vCity'])?$getClientDetail['vCity']:"";

					// Current country for United States,Canada Show only Taxi, Car Pool, shuttle else Show All
					if ($this->data['SHOWALLTYPE']=='Yes') {
						$resData['CurrentCountry']="ShowAll";
					} else {
						$source_country =$this->GetCountryFromAddressOrLatLong($latitude.','.$longitude,'latlong');
						$resData['CurrentCountry']=$source_country['long_name'];
					}
					/*echo "<pre>";print_r($resData);exit;*/
					$Data['data'] = $resData;
					$Data['msg']  = "Login Successfully";
					$Data['role'] = "rider";
				}else{
					$Data['msg'] = "Your Account Is Inactive";
				}
			} else {
				$Data['msg'] = "OTP Is Not Valid";
			}
		}else if($role=='owner'){
			//
			$ownerDetail = $this->webservices_model->getDetailByFieldValue('vehicle_owner','vForgotPasswordString',$iOTP);
			if($ownerDetail){
				if($ownerDetail['eVerifiedMobile']=='Yes' && $ownerDetail['eApprovalStatus']=='Approved'){
					$iVehicleOwnerId = $ownerDetail['iVehicleOwnerId'];
					$update_owner['vPassword'] = $newPassword;
					$update_owner['vForgotPasswordString'] = '';
					$res = $this->webservices_model->update_owner_detail($update_owner, $iVehicleOwnerId);
					// send login info
					$ownerdetail = $this->webservices_model->getDetailByFieldValue('vehicle_owner','iVehicleOwnerId',$iVehicleOwnerId);
					$base_upload=$this->data['base_upload'];
					$base_path = $this->data['base_path'];
					$file_path = $base_path.'uploads/vehicle_owner/'.$ownerdetail['iVehicleOwnerId'].'/'.$ownerdetail['vProfile'];
					if (file_exists($file_path)) {
						$image_Url = $base_upload.'vehicle_owner/'.$ownerdetail['iVehicleOwnerId'].'/'.$ownerdetail['vProfile'];
					}else{
						$image_Url = $base_upload.'plash-holder.png';
					}
					$ownerData['iVehicleOwnerId'] = $ownerdetail['iVehicleOwnerId'];
					$ownerData['vFirstName'] = $ownerdetail['vFirstName'];
					$ownerData['vLastName'] = $ownerdetail['vLastName'];
					$ownerData['fullname'] = $ownerdetail['vFirstName'].' '.$ownerdetail['vLastName'];
					$ownerData['vEmail'] = $ownerdetail['vEmail'];
					$ownerData['vMobileNo'] = $ownerdetail['vMobileNo'];
					$ownerData['eStatus'] = $ownerdetail['eStatus'];
					$ownerData['vProfile'] = $ownerdetail['vProfile'];
					$ownerData['image_Url'] = $image_Url;
					$ownerData['tBusinessName'] = $ownerdetail['tBusinessName'];
					$ownerData['eBusinessType'] = $ownerdetail['eBusinessType'];
					$ownerData['vOtherBusinessType'] = $ownerdetail['vOtherBusinessType'];
					$ownerData['tAddress'] = ($ownerdetail['tAddress']=='')?'':$ownerdetail['tAddress'];
					$ownerData['iCountryId'] = ($ownerdetail['iCountryId'])?$ownerdetail['iCountryId']:"";
					$ownerData['vCountry'] = ($ownerdetail['vCountry'])?$ownerdetail['vCountry']:"";
					$ownerData['iStateId'] = ($ownerdetail['iStateId'])?$ownerdetail['iStateId']:"";
					$ownerData['vState'] = ($ownerdetail['vState'])?$ownerdetail['vState']:"";
					$ownerData['iCityId'] = ($ownerdetail['iCityId'])?$ownerdetail['iCityId']:"";
					$ownerData['vCity'] = ($ownerdetail['vCity'])?$ownerdetail['vCity']:"";
					$ownerData['vPostalCode'] = ($ownerdetail['vPostalCode'])?$ownerdetail['vPostalCode']:"";
					$ownerData['eOutOfService'] = $ownerdetail['eOutOfService'];
					$Data['data'] = $ownerData;
					$Data['msg']  = "Login Successfully";
					$Data['role'] = "owner";
				}else if ($getOwnerDetail['eVerifiedMobile']=='Yes' && $getOwnerDetail['eApprovalStatus']=='Pending'){
					$Data['msg'] = "Your account pending site admin approval. Please contact for further assistance.";
				}else if ($getOwnerDetail['eApprovalStatus']=='Rejected'){
					$Data['msg'] = "Your account is rejected by site admin. Please contact for further assistance.";
				}else{
					$Data['msg'] = "Your Account Is Inactive";
				}
			}else{
				$Data['msg'] = "OTP Is Not Valid";
			}
		}else if($role=='driver'){
			$driverDetail = $this->webservices_model->getDetailByFieldValue('driver','vForgotPasswordString',$iOTP);
			if(count($driverDetail)>0){
				if($driverDetail['eVerifiedMobile']=='Yes' && $driverDetail['eStatus']=='Active'){
					$iDriverId = $driverDetail['iDriverId'];
					$update_driver['vPassword'] = $newPassword;
					$update_driver['vForgotPasswordString'] = '';
					$res = $this->webservices_model->update_driver_detail($update_driver, $iDriverId);
					// send login info
					$driverDetail = $this->webservices_model->getDetailByFieldValue('driver','iDriverId',$iDriverId);
					$base_upload=$this->data['base_upload'];
					$base_path = $this->data['base_path'];
					$driverDetail['vPassword'] = $this->decrypt($driverDetail['vPassword']);
					$driverDetail['vDriverFullname'] = $driverDetail['vFirstName']." ".$driverDetail['vLastName'];
					if($driverDetail['vProfileImage']){
						$driverDetail['image_Url'] = $base_upload.'driver/'.$driverDetail['iDriverId'].'/'.$driverDetail['vProfileImage'];
					}
					else {
						$driverDetail['image_Url'] = $base_upload.'red-driver.png';
					}   
					
					$change_status = $this->webservices_model->update_login_status($vEmail);
					$all_driver_trips = $this->webservices_model->get_all_driver_trips($driverDetail['iDriverId']);
					$total_driver_trips = count($all_driver_trips);
					$total_rating_cnt = 0;
					foreach ($all_driver_trips as $key => $value) {
						$total_rating_cnt = ($total_rating_cnt + $value['fRating']);
					}
					$driverDetail['average_rating'] = number_format(($total_rating_cnt/$total_driver_trips),2);
					$car_details = $this->webservices_model->get_car_details($driverDetail['iDriverId']);
					$driverDetail['vCompany'] = ($car_details['vCompany'])?$car_details['vCompany']:"";
					$driverDetail['vModelName'] = ($car_details['vModelName'])?$car_details['vModelName']:"";
					$driverDetail['vPlateNo'] = ($car_details['vRegistrationNo'])?$car_details['vRegistrationNo']:"";
					$Data['data']= $driverDetail;
					$Data['msg'] = "Login Successfully";
					$Data['role'] = "driver";
				}else if ($driverDetail['eVerifiedMobile']=='No'){
					$Data['msg'] = "Your Mobile No is not varified";
				}else{
					$Data['msg'] = "Your Account Is Inactive";
				}
			}else{
				$Data['msg'] = "User Not Exist";
			}
		}else{
			$Data['msg'] = "Role Is Not Valid";
		}	
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function reSendInvoiceEmail(){
		if($this->input->post('iTripId')){
			$trip_id = $this->input->post('iTripId');
			$trip_Details = $this->webservices_model->gettrip($trip_id);
			if($trip_Details){
				if($trip_Details['eStatus']=='Complete'){
					$tripDetails = $this->webservices_model->get_single_trip_details($trip_id);
					$mycurrency = $this->webservices_model->get_city_currency($tripDetails['iCityId']);
					// $this->printthisexit($tripDetails);
					$traveldate = $tripDetails['dTripDate'];
					$traveldate = date_create($tripDetails['dTripDate']);
					$traveldate = date_format($traveldate, 'jS F Y');
					$route_map_url = $tripDetails['tRouteImgURL'];
					$line_img_url = $this->data['base_url'].'assets/image/invoice/shep.png';
					$red_marker_url = $this->data['base_url'].'assets/image/invoice/red.png';
					$green_marker_url = $this->data['base_url'].'assets/image/invoice/green.png';
					$tripstarttime = date_create($tripDetails['dTripDate']);
					$tripstarttime = date_format($tripstarttime, 'g:i A');
					$source_address = $tripDetails['vPickupLocation'];
					$tripendtime = date_create($tripDetails['dRideEndDate']);
					$tripendtime = date_format($tripendtime, 'g:i A');
					$destination_address = $tripDetails['vDestinationLocation'];
					$car_name = $tripDetails['vModelName'];
					$trip_full_start_time = date('H:i:s',strtotime($tripDetails['dTripDate']));
					$my_base_url = $this->data['base_url'];
					// code for displaying trip
					$trip_star_rating_display = $this->StarRating($tripDetails['fRating']);
					// end of code for displaying trip

					$travel_fare = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fFinalPayment'],2);
					$total_distance_in_miles = $tripDetails['fDistance'].' '.strtoupper($tripDetails['eDistanceUnit']);

					if($tripDetails['fTotalMinute']){
						$tripDetails['fTotalMinute'] = ceil($tripDetails['fTotalMinute']);
						if($tripDetails['fTotalMinute']<=1){
							$total_duration_in_mins = ceil($tripDetails['fTotalMinute']).' MIN';
						}else {
							$fTotalMinute = ceil($tripDetails['fTotalMinute']);
							if($fTotalMinute>=60){
								$hours = intval($fTotalMinute/60);
								$minutes = $fTotalMinute - ($hours * 60);

								if($hours>1){
									if($minutes>1){ $total_duration_in_mins = $hours.' HRS '.$minutes.' MINS'; }
									else { $total_duration_in_mins = $hours.' HRS '.$minutes.' MIN'; }
								}else{
									if($minutes>1){ $total_duration_in_mins = $hours.' HR '.$minutes.' MINS'; }
									else { $total_duration_in_mins = $hours.' HR '.$minutes.' MIN'; }
								}
							}else {
								$total_duration_in_mins = ceil($tripDetails['fTotalMinute']).' MINS';
							}
						}
					}else {
						$total_duration_in_mins = '0 MIN';    
					}

					$base_fare = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fBaseFare'], 2);
					$distance_fare = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fPerMileFare'], 2);
					$duration_fare = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fPerMinFare'], 2);
					$sub_total = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['dSubtotalPayment'], 2);
					$saferidefees = $mycurrency['vCurrencyCode'].' '.number_format(($tripDetails['dNewsLetterPromocodeDiscount'] + $tripDetails['dInvitePromoCodeDiscount']),2);
					$minpayment = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['MinimumFare'],2);
					$finalpayment = $mycurrency['vCurrencyCode'].' '.number_format($tripDetails['fFinalPayment'],2);
					if($tripDetails['vProfileImage']){
						$driver_profile_img = $this->data['base_upload'].'driver/'.$tripDetails['iDriverId'].'/'.$tripDetails['vProfileImage']; 
					}else {
						$driver_profile_img = $this->data['base_upload'].'red-driver.png';
					}
					
					$driver_name = $tripDetails['vFirstName'].' '.$tripDetails['vLastName'];
					$credit_card_image_name = $this->webservices_model->get_card_image_name($tripDetails['iTransactionId']);

					if($credit_card_image_name){
						$card_url = $this->data['base_upload'].'credit_card/'.$credit_card_image_name['vCardImage'];
					}else{
						$card_url = $this->data['base_upload'].'credit_card/cash.png';
					}
					$emailmins = $this->ConvertHrToMin($totalmins);
					$all_partners = $this->webservices_model->get_all_trip_partners($tripDetails['iClientId'],$trip_id);
					$internalpartnernames = '';
					for($a=0;$a<count($all_partners);$a++){
						$all_internal_partners_names = $this->webservices_model->get_all_trip_internal_partners($all_partners[$a]['iClientId'],$trip_id);
						
						if(count($all_internal_partners_names)==1){
							$internalpartnernames = 'You rode with partner '.$all_internal_partners_names[0]['vFirstName'].' '.$all_internal_partners_names[0]['vLastName'].' and '.$tripDetails['ClientFirstName'].' '.$tripDetails['ClientLastName']; 
						}else if(count($all_internal_partners_names)>1){
							for($i=0;$i<count($all_internal_partners_names);$i++){
								if(($i==0) && (count($all_internal_partners_names)!=2)){
									$internalpartnernames = 'You rode with partner '.$internalpartnernames.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'].',';
								}else if(($i==0) && (count($all_internal_partners_names)==2)){
									$internalpartnernames = 'You rode with partner '.$internalpartnernames.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'];
								}else if($i==(count($all_internal_partners_names)-1)){

									$internalpartnernames = $internalpartnernames.','.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'];
								}else if($i==(count($all_internal_partners_names)-2)){
									$internalpartnernames = $internalpartnernames.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'];
								}else {
									$internalpartnernames = $internalpartnernames.$all_internal_partners_names[$i]['vFirstName'].' '.$all_internal_partners_names[$i]['vLastName'].','; 
								}
							}
							$internalpartnernames = $internalpartnernames.' and '.$tripDetails['ClientFirstName'].' '.$tripDetails['ClientLastName'];   
						}else if(count($all_internal_partners_names)==0){
							$internalpartnernames = 'You rode with partner '.$tripDetails['ClientFirstName'].' '.$tripDetails['ClientLastName'];
						}
					}

					$bodyArr = array("#CARD_IMAGE#","#TRAVELDATE#","#MAP_IMG_URL#","#LINE_IMG_URL#","#RED_MARKER_IMG#","#GREEN_MARKER_IMG#","#TRIP_START_TIME#","#SOURCE_ADDRESS#","#TRIP_END_TIME#","#DESTINATION_ADDRESS#","#CAR_NAME#","#TRIP_FULL_START_TIME#","#TRAVEL_FARE#","#TOTAL_DISTANCE_IN_MILES#","#TOTAL_TIMES_IN_MINS#","#SUBTOTAL#","#DISCOUNTS#","#FINAL_PAYMENT#","#DRIVER_PROFILE_IMG#","#DRIVER_NAME#","#PARTNER_NAME#","#BASE_FARE#","#DISTANCE_FARE#","#DURATION_FARE#","#STAR_RATING#","#IMAGE_URL#","#FIXED_RATE#");
					$postArr = array($card_url,$traveldate,$route_map_url,$line_img_url,$red_marker_url,$green_marker_url,$tripstarttime,$source_address,$tripendtime,$destination_address,$car_name,$trip_full_start_time,$travel_fare,$total_distance_in_miles,$emailmins,$sub_total,$saferidefees,$finalpayment,$driver_profile_img,$driver_name,$internalpartnernames,$base_fare,$distance_fare,$duration_fare,$trip_star_rating_display,$my_base_url,$minpayment);

					$client_email_address = $tripDetails['ClientEmail'];
					// $client_email_address = "rajiv.khandar@techiestown.com";
					$sendClient=$this->Send("CUSTOMER_INVOICE","Client",$client_email_address,$bodyArr,$postArr);
					// end of temp code
					$dataarr['msg'] = 'Success';

					// $this->printthisexit($tripDetails['eStatus']);
					$data['msg'] = 'Success';
				}else if ($trip_Details['eStatus']=='Cancel'){
					$dataarr['msg'] = 'Success';
				}else{
					# Pending / Confirmed
					$data['msg'] = 'Trip Is Not Finished';
				}
			}else{
				$data['msg'] = 'Trip Not Exist';
			}
		}else {
			$data['msg'] = 'Failure';
		}
		header('Content-type: application/json');
		$main = json_encode($data);
		echo $main;
		exit;
	}

	function getLicenceDetails(){
		if($this->input->post('iDriverId')){
			$iDriverId=$this->input->post('iDriverId');
			$LicenceInfo=$this->webservices_model->GetLicenceInfo($this->input->post('iDriverId'));
			if(count($LicenceInfo)>0){
				// $resArr=$LicenceInfo;
				$resArr['iLicenceId']=$LicenceInfo['iLicenceId'];
				$resArr['iDriverId']=$LicenceInfo['iDriverId'];
				$resArr['vLicenceExpiryDate']=$LicenceInfo['vLicenceExpiryDate'];

				if($LicenceInfo['vPoliceReport']!=''){
					$resArr['vPoliceReport']=$this->data['base_url'].'uploads/police_report/'.$iDriverId.'/'.$LicenceInfo['vPoliceReport'];
				}else{
					$resArr['vPoliceReport']='';
				}
				if($LicenceInfo['tRegularLicense']!=''){
					$resArr['tRegularLicense']=$this->data['base_url'].'uploads/regular_license/'.$iDriverId.'/'.$LicenceInfo['tRegularLicense'];
				}else{
					$resArr['tRegularLicense']='';
				}	
				$resArr['eVerifiedStatus']=$LicenceInfo['eVerifiedStatus'];
				$data['data'] = $resArr;
				$data['msg'] = "Success";
			}else{
				$data['msg'] = "Licence Not Found";
			}
		}else{
			$data['msg'] = "Error";
		}
		echo json_encode($data);exit;		
	}

	function SetDriverLicenceInfo(){
		if($this->input->post('iDriverId')){
			$flag=$this->webservices_model->varifyDriverLicenceExistance($this->input->post('iDriverId'));
			if($flag==0){
				if(trim($this->input->post('iDriverId')) == '' || trim($this->input->post('vLicenceExpiryDate')) == ''  || trim($_FILES['tRegularLicense']['name']) == '' ){
					$data['msg'] = "Error";
					echo json_encode($data);
					exit;					
				}
			}
			$iDriverId=$this->input->post('iDriverId');
			if ($_FILES['tRegularLicense']['name'] != '') {
                $size = array();
                $size['width'] = '228';
                $size['height'] = '228';
                $size['width2'] = '57';
                $size['height2'] = '57';
                $vehicledoc = mysql_real_escape_string($_FILES['tRegularLicense']['name']);
                $tRegularLicenseDocument = preg_replace("/[^.A-Za-z0-9\-]/", "",$vehicledoc);
                $licence_info['tRegularLicense'] = str_replace(':', '', $tRegularLicenseDocument);
                $image_uploaded = $this->do_upload_img($iDriverId, 'regular_license', 'tRegularLicense', $size);
        		/* if ($flag>0) {
            	  	$upload_path = $this->config->item('base_path');
    				$url = $upload_path . "/uploads/regular_license/".$iDriverId;
    				array_map('unlink', glob($url . "/*"));
        		}*/
            }
			if ($_FILES['vPoliceReport']['name'] != '') {
                $size = array();
                $size['width'] = '228';
                $size['height'] = '228';
                $size['width2'] = '57';
                $size['height2'] = '57';
                $vehicledoc = mysql_real_escape_string($_FILES['vPoliceReport']['name']);
                $vPoliceReportDocument = preg_replace("/[^.A-Za-z0-9\-]/", "",$vehicledoc);
                $licence_info['vPoliceReport'] = str_replace(':', '', $vPoliceReportDocument);
                $image_uploaded = $this->do_upload_img($iDriverId, 'police_report', 'vPoliceReport', $size);
        		/* if ($flag>0) {
            	  	$upload_path = $this->config->item('base_path');
    				$url = $upload_path . "/uploads/police_report/".$iDriverId;
    				array_map('unlink', glob($url . "/*"));
        		}*/
            }
			$licence_info['iDriverId']=$iDriverId;
			$licence_info['vLicenceExpiryDate']=date('Y-m-d',strtotime($this->input->post('vLicenceExpiryDate')));
			$licence_info['eVerifiedStatus']='Not Checked';
			if($flag>0){
				$this->webservices_model->update_Licence($licence_info,$iDriverId);
			}else{
				$this->webservices_model->save_data($licence_info,'drivers_licence');
			}
			$data['msg'] = "Success";
		}else{
			$data['msg'] = "Error";
		}
		echo json_encode($data);exit;
	}

	function varifyMobileOTP(){
		if($this->input->post('Type')!='' && $this->input->post('OTP') != '' && $this->input->post('iMobileNo') != ''){
			$iMobileNo = $this->input->post('iMobileNo');
			$type = $this->input->post('Type');
			$iUserId=$this->input->post('iUserId');
			if($type=='Rider'){
				$clientMobileExist = $this->webservices_model->getDetailByFieldValue('client','iMobileNo',$iMobileNo);
				if($clientMobileExist){
					$data['msg'] = "Mobile Number Exist";
					header('Content-type: application/json');
					$main = json_encode($data);
					echo $main;
					exit;
				}
			}

			if($type =='Owner'){
				$chk_vo_mobile1 = $this->webservices_model->getvarifiedNumberOwner($iMobileNo,$iUserId);
				if ($chk_vo_mobile1 == 'exists'){
					
					$data['msg'] = "Mobile Number Exist";
					header('Content-type: application/json');
					$main = json_encode($data);
					echo $main;
					exit; 
				}
			}
			if($type=='Driver'){

				$id=$this->webservices_model->getdriver_ownerID($iUserId);
				$iVehicleOwnerId=$id['iVehicleOwnerId'];
				$chk_vo_mobile1 = $this->webservices_model->getVarifyDriverNo($iMobileNo,$iVehicleOwnerId,$iUserId);
				if ($chk_vo_mobile1=='Mobexists'){
					$data['msg'] = "Mobile Number Exist";
					header('Content-type: application/json');
					$main = json_encode($data);
					echo $main;
					exit; 
				}
			}
			$res=$this->webservices_model->varifyMobileOtp($this->input->post('Type'),$this->input->post('iMobileNo'),$this->input->post('OTP'));
			if($res>0){
				$Data['msg'] = "Success";
			}else{
				$Data['msg'] = "Error";
			}
		}else{
			$Data['msg'] = "Error";
		}
		echo json_encode($Data);exit;
	}

	function varifyOTP(){
		$iOTP = trim($this->input->post('iOTP'));
		$role=$this->input->post('role');
		if ($iOTP=='') {
			$Data['msg'] = "OTP Is Not Valid";
			header('Content-type: application/json');
			$main = json_encode($Data);
			echo $main;
			exit;
		}
		if($role=='driver'){
			$driverDetail = $clientDetail = $this->webservices_model->getDetailByOTP('driver',$iOTP);

			$ownerdetail=$this->webservices_model->getOwner_details($driverDetail['iVehicleOwnerId']);

			if($ownerdetail['eApprovalStatus']=='Approved' && $ownerdetail['eVerifiedMobile']=='Yes' && $ownerdetail['eStatus']=='Active'){
				if(count($driverDetail)>0){
					$iDriverId = $driverDetail['iDriverId'];
					$update_driver['vForgotPasswordString'] = '';
					$update_driver['eStatus'] = 'Active';
					$update_driver['eVerifiedMobile'] = 'Yes';
					$update_driver['eLoginStatus'] = 'Yes';
					$res = $this->webservices_model->update_driver_detail($update_driver, $iDriverId);
					// send login info
					$driverDetail = $this->webservices_model->getDetailByFieldValue('driver','iDriverId',$iDriverId);
					$base_upload=$this->data['base_upload'];
					$base_path = $this->data['base_path'];
					$driverDetail['vPassword'] = $this->decrypt($driverDetail['vPassword']);
					$driverDetail['iMobileNo'] = $driverDetail['iMobileNo1'];
					$driverDetail['vDriverFullname'] = $driverDetail['vFirstName']." ".$driverDetail['vLastName'];
					if($driverDetail['vProfileImage']){
						$driverDetail['image_Url'] = $base_upload.'driver/'.$driverDetail['iDriverId'].'/'.$driverDetail['vProfileImage'];
					}
					else {
						$driverDetail['image_Url'] = $base_upload.'red-driver.png';
					}   
					
					$change_status = $this->webservices_model->update_login_status($vEmail);
					$all_driver_trips = $this->webservices_model->get_all_driver_trips($driverDetail['iDriverId']);
					$total_driver_trips = count($all_driver_trips);
					$total_rating_cnt = 0;
					foreach ($all_driver_trips as $key => $value) {
						$total_rating_cnt = ($total_rating_cnt + $value['fRating']);
					}
					$driverDetail['average_rating'] = number_format(($total_rating_cnt/$total_driver_trips),2);
					$car_details = $this->webservices_model->get_car_details($driverDetail['iDriverId']);
					$driverDetail['vCompany'] = ($car_details['vCompany'])?$car_details['vCompany']:"";
					$driverDetail['vModelName'] = ($car_details['vModelName'])?$car_details['vModelName']:"";
					$driverDetail['vPlateNo'] = ($car_details['vRegistrationNo'])?$car_details['vRegistrationNo']:"";
					$Data['data']= $driverDetail;
					$Data['msg'] = "Login Successfully";
					$Data['role'] = "driver";
				}else{
					$Data['msg'] = "OTP Is Not Valid";
				}
			}else if($ownerdetail['eVerifiedMobile']=='Yes' && $ownerdetail['eApprovalStatus']=='Pending' && $ownerdetail['eStatus']=='Active'){
				$Data['msg'] = "Owner account pending site admin approval. Please contact for further assistance.";
			}else if($ownerdetail['eApprovalStatus']=='Rejected'){
				$Data['msg'] = "Owner account is rejected by site admin. Please contact for further assistance.";
			}else if($ownerdetail['eVerifiedMobile']=='No'){
				$Data['msg'] = "Owner Mobile Number Is Not Verified.";
			}else if($ownerdetail['eStatus']=='Inactive'){
				$Data['msg'] = "Owner Account Is Freezed By Admin.";
			}else{
				$Data['msg'] = "Owner Account Is Freezed By Admin.";
			}	
		}else if($role=='rider'){
			$clientDetail = $this->webservices_model->getDetailByOTP('client',$iOTP);
			if ($clientDetail) {
				$latitude = $this->input->post('latitude');
				$longitude = $this->input->post('longitude');
				// Change status and varify, remove 
				$update_rider['iClientId'] = $clientDetail['iClientId'];
				$update_rider['eVerifiedMobile'] = 'Yes';
				$update_rider['iOTP'] = '';
				$update_rider['vActivationCode'] = '';
				$update_rider['eStatus'] = 'Active';
				// $this->printthis($update_rider);
				// Update details
				$ClientId = $this->webservices_model->update_client_detail($update_rider);
				// Get latest details
				$getClientDetail = $this->webservices_model->getClientDetail($clientDetail['iClientId']);

				$base_path = $this->data['base_path'];
				$file_path = $base_path.'uploads/client/'.$getClientDetail['iClientId'].'/'.$getClientDetail['vProfileImage'];
					
				if($getClientDetail['eImageType']=='withurl'){
					$image_Url = $getClientDetail['vProfileImage'];
				}
				else if($getClientDetail['eImageType']=='withouturl'){
					if (file_exists($file_path)) {
						$image_Url = $base_upload.'client/'.$getClientDetail['iClientId'].'/'.$getClientDetail['vProfileImage'];
					}else{
						$image_Url = $base_upload.'plash-holder.png';
					}
				}else {
					$image_Url = $base_upload.'plash-holder.png';
				}
				
				$resData['iClientId'] = $getClientDetail['iClientId'];
				$resData['fullname'] = $getClientDetail['vFirstName'].' '.$getClientDetail['vLastName'];
				$resData['vFirstName'] = $getClientDetail['vFirstName'];
				$resData['vLastName'] = $getClientDetail['vLastName'];

				if(isset($getClientDetail['tAddress'])){
					$resData['tAddress'] = $getClientDetail['tAddress'];
				}else {
					$resData['tAddress'] = "";
				}
				
				$resData['vEmail'] = $getClientDetail['vEmail'];
				$resData['iMobileNo'] = $getClientDetail['iMobileNo'];
				$resData['eStatus'] = $getClientDetail['eStatus'];
				$resData['vProfileImage'] = $getClientDetail['vProfileImage'];
				$resData['vPassword'] = $getClientDetail['vPassword'];
				$resData['image_Url'] = $image_Url;
				$resData['vPromotionCode'] = ($getClientDetail['vPromotionCode'])?$getClientDetail['vPromotionCode']:"";
				$resData['vPostalCode'] = ($getClientDetail['vPostalCode'])?$getClientDetail['vPostalCode']:"";
				$resData['iCountryId'] = ($getClientDetail['iCountryId'])?$getClientDetail['iCountryId']:"";
				$resData['vCountry'] = ($getClientDetail['vCountry'])?$getClientDetail['vCountry']:"";
				$resData['iStateId'] = ($getClientDetail['iStateId'])?$getClientDetail['iStateId']:"";
				$resData['vState'] = ($getClientDetail['vState'])?$getClientDetail['vState']:"";
				$resData['iCityId'] = ($getClientDetail['iCityId'])?$getClientDetail['iCityId']:"";
				$resData['vCity'] = ($getClientDetail['vCity'])?$getClientDetail['vCity']:"";

				// Current country for United States,Canada Show only Taxi, Car Pool, shuttle else Show All
				if ($this->data['SHOWALLTYPE']=='Yes') {
					$resData['CurrentCountry']="ShowAll";
				} else {
					$source_country =$this->GetCountryFromAddressOrLatLong($latitude.','.$longitude,'latlong');
					$resData['CurrentCountry']=$source_country['long_name'];
				}
				/*echo "<pre>";print_r($resData);exit;*/
				$Data['data'] = $resData;
				$Data['msg']  = "Login Successfully";
				$Data['role'] = "rider";
				// $this->printthisexit($getClientDetail);
			} else {
				$Data['msg'] = "OTP Is Not Valid";
			}
		}else if($role=='owner'){
			$ownerDetail = $this->webservices_model->getDetailByOTP('vehicle_owner',$iOTP);
			if ($ownerDetail) {
				$iVehicleOwnerId = $ownerDetail['iVehicleOwnerId'];
				$update_owner['eStatus'] = 'Active';
				$update_owner['eVerifiedMobile'] = 'Yes';
				$update_owner['iOTP'] = '';
				$update_owner['vActivationCode'] = '';
				// Verify Owner Mobile
				$res = $this->webservices_model->update_owner_detail($update_owner, $iVehicleOwnerId);
				// Verify Driver Mobile
				$res = $this->webservices_model->updateDriverByOwner($update_owner, $iVehicleOwnerId);

				if($ownerDetail['eApprovalStatus']=='Approved'){
					$ownerdetail = $this->webservices_model->getOwnerDetailbyId($iVehicleOwnerId);
					
					$file_path = $base_path.'uploads/vehicle_owner/'.$iVehicleOwnerId.'/'.$ownerdetail['vProfile'];
					if (file_exists($file_path)) {
						$image_Url = $base_upload.'vehicle_owner/'.$iVehicleOwnerId.'/'.$ownerdetail['vProfile'];
					}else{
						$image_Url = $base_upload.'plash-holder.png';
					}
					$ownerData['iVehicleOwnerId'] = $iVehicleOwnerId;
					$ownerData['vFirstName'] = $ownerdetail['vFirstName'];
					$ownerData['vLastName'] = $ownerdetail['vLastName'];
					$ownerData['fullname'] = $ownerdetail['vFirstName'].' '.$ownerdetail['vLastName'];
					$ownerData['vEmail'] = $ownerdetail['vEmail'];
					$ownerData['vMobileNo'] = $ownerdetail['vMobileNo'];
					$ownerData['eStatus'] = $ownerdetail['eStatus'];
					$ownerData['vProfile'] = $ownerdetail['vProfile'];
					$ownerData['image_Url'] = $image_Url;
					$ownerData['tBusinessName'] = $ownerdetail['tBusinessName'];
					$ownerData['eBusinessType'] = $ownerdetail['eBusinessType'];
					$ownerData['vOtherBusinessType'] = $ownerdetail['vOtherBusinessType'];
					$ownerData['tAddress'] = ($ownerdetail['tAddress']=='')?'':$ownerdetail['tAddress'];
					$ownerData['iCountryId'] = ($ownerdetail['iCountryId'])?$ownerdetail['iCountryId']:"";
					$ownerData['vCountry'] = ($ownerdetail['vCountry'])?$ownerdetail['vCountry']:"";
					$ownerData['iStateId'] = ($ownerdetail['iStateId'])?$ownerdetail['iStateId']:"";
					$ownerData['vState'] = ($ownerdetail['vState'])?$ownerdetail['vState']:"";
					$ownerData['iCityId'] = ($ownerdetail['iCityId'])?$ownerdetail['iCityId']:"";
					$ownerData['vCity'] = ($ownerdetail['vCity'])?$ownerdetail['vCity']:"";
					$ownerData['vPostalCode'] = ($ownerdetail['vPostalCode'])?$ownerdetail['vPostalCode']:"";
					$ownerData['eOutOfService'] = $ownerdetail['eOutOfService'];
					$Data['data'] = $ownerData;
					$Data['msg']  = "Login Successfully";
					$Data['role'] = "owner";
				}else if($ownerDetail['eApprovalStatus']=='Pending'){
					$Data['msg'] = "Your account pending site admin approval. Please contact for further assistance.";
				}else if($ownerDetail['eApprovalStatus']=='Rejected'){
					$Data['msg'] = "Your account is rejected by site admin. Please contact for further assistance.";
				}
			} else {
				$Data['msg'] = "OTP Is Not Valid";
			}
		}else{   
			$Data['msg'] = "Role Is Not Valid";
		} 
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function getDriverLatLong(){
		if($this->input->post('iDriverId') != ''){
			$res=$this->webservices_model->getDriverLatLong($this->input->post('iDriverId'));
			if(count($res)>0){
				$Data['msg'] = "Success";
				$Data['data']['dLatitude'] = $res['dLatitude'];
				$Data['data']['dLongitude'] = $res['dLongitude'];
				$Data['data']['iDriverId'] = $res['iDriverId'];
			}else{
				$Data['msg'] = "Error";
			}
		}else{
			$Data['msg'] = "Error";
		}
		echo json_encode($Data);exit;
	}

	function SendChangeMobileOTP(){
		if($this->input->post('Type')=='Rider' && $this->input->post('iUserId') != '' && $this->input->post('iMobileNo') != ''){
			
			$iMobileNo=$this->input->post('iMobileNo');
			$clientMobileExist = $this->webservices_model->getDetailByFieldValue('client','iMobileNo',$iMobileNo);
			if($clientMobileExist){
				$data['msg'] = "Mobile Number Exist";
				header('Content-type: application/json');
				$main = json_encode($data);
				echo $main;
				exit;
			}
			$iOTP = random_string($type = 'numeric', 4);
			$update_rider['iClientId'] = $this->input->post('iUserId');
			$update_rider['iMobileOTP'] = $iOTP;
			$update_rider['vMobileTemp'] = $this->input->post('iMobileNo');
			$res = $this->webservices_model->update_client_detail($update_rider);
			$extraChr = array(" ", "(", ")", "-", "[", "]");
			$update_rider['vMobileTemp'] = '+'.str_replace($extraChr, "", $update_rider['vMobileTemp']);
			$smsText = 'Your OneTouchCab verification code is: '.$iOTP;
			require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
			$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
			$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
			$client = new Services_Twilio($AccountSid, $AuthToken);
			try {
				$sms[] = $client->account->messages->sendMessage("+18562882821",$update_rider['vMobileTemp'],$smsText);
				$Data['msg'] = "Success";
			}catch (Exception $e) {
			    $Data['msg'] = "SMS Sending Failure";
			}
			
		}else if($this->input->post('Type')=='Driver' && $this->input->post('iUserId') != '' && $this->input->post('iMobileNo') != '' ){

			$iUserId=$this->input->post('iUserId');
			$iMobileNo=$this->input->post('iMobileNo');
			$id=$this->webservices_model->getdriver_ownerID($iUserId);
			$iVehicleOwnerId=$id['iVehicleOwnerId'];
			$chk_vo_mobile1 = $this->webservices_model->getVarifyDriverNo($iMobileNo,$iVehicleOwnerId,$iUserId);
			if ($chk_vo_mobile1=='Mobexists'){
				$data['msg'] = "Mobile Number Exist";
				header('Content-type: application/json');
				$main = json_encode($data);
				echo $main;
				exit; 
			}
			$iOTP = random_string($type = 'numeric', 4);
			$update_driver['iMobileOTP'] = $iOTP;
			$update_driver['vMobileTemp'] = $this->input->post('iMobileNo');
			$res = $this->webservices_model->update_driver_detail($update_driver,$this->input->post('iUserId'));
			$extraChr = array(" ", "(", ")", "-", "[", "]");
			$update_rider_no = '+'.str_replace($extraChr, "", $update_driver['vMobileTemp']);
			
			$smsText = 'Your OneTouchCab verification code is: '.$iOTP;
			require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
			$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
			$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
			$client = new Services_Twilio($AccountSid, $AuthToken);
			try {
				$sms[] = $client->account->messages->sendMessage("+18562882821",$update_rider_no,$smsText);
				$Data['msg'] = "Success";
			}catch (Exception $e) {
			    $Data['msg'] = "SMS Sending Failure";
			}
			

		}else if($this->input->post('Type')=='Owner' && $this->input->post('iUserId') != '' && $this->input->post('iMobileNo') != '' ){

			$iUserId=$this->input->post('iUserId');
			$iMobileNo=$this->input->post('iMobileNo');
			$chk_vo_mobile1 = $this->webservices_model->getvarifiedNumberOwner($iMobileNo,$iUserId);
			if ($chk_vo_mobile1 == 'exists'){
				
				$data['msg'] = "Mobile Number Exist";
				header('Content-type: application/json');
				$main = json_encode($data);
				echo $main;
				exit; 
			}

			$iOTP = random_string($type = 'numeric', 4);
			$update_owner['iMobileOTP'] = $iOTP;
			$update_owner['vMobileTemp'] = $this->input->post('iMobileNo');
			$res = $this->webservices_model->update_owner_detail($update_owner,$this->input->post('iUserId'));
			$extraChr = array(" ", "(", ")", "-", "[", "]");
			$update_rider_no = '+'.str_replace($extraChr, "", $this->input->post('iMobileNo'));
			$smsText = 'Your OneTouchCab verification code is: '.$iOTP;
			require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
			$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
			$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
			$client = new Services_Twilio($AccountSid, $AuthToken);
			try {
				$sms[] = $client->account->messages->sendMessage("+18562882821",$update_rider_no,$smsText);
				$Data['msg'] = "Success";
			}catch (Exception $e) {
			    $Data['msg'] = "SMS Sending Failure";
			}
		}else{
			$Data['msg'] = "Error";
		}
		echo json_encode($Data);exit;
	}

	//---------------------------- 22 Dec 2016 ----------------------------
	function getVehicleDetails(){
		$base_upload=$this->data['base_upload'];
		// Expected perameters options
			// circleType [Taxi / CarPool / Local / OutStation / Shuttle / FixedRide]
			// for Taxi : source_address, destination_address, booktype [now / later]
			// for CarPool : source_address, destination_address, booktype [now / later], book_time
			// for Local : source_address, booktype [now / later]
			// for OutStation : source_address, booktype [now / later]
			// for Shuttle : source_address, booktype [now / later]
			// for FixedRide : ride_id, booktype [now / later]
		$givenData = $this->input->post();
		if($givenData['circleType']){
			if($givenData['circleType']=="FixedRide"){
				$rideDetail = $this->webservices_model->get_ride_detail($givenData['ride_id']);
				$sourceCityDetail = $this->webservices_model->get_one_city_details_byid($rideDetail['iCityId']);
				$vehicleDetails = $this->webservices_model->get_vehicle_detail_for_fix_ride($givenData['ride_id'],$sourceCityDetail['iCityId']);
				if ($rideDetail['eFaretype']=='Local') {
					$ridelocationArr[] = 'Local';
					$ridelocationArr[] = 'Both';
				} else {
					$ridelocationArr[] = 'Outstation';
					$ridelocationArr[] = 'Both';
				}
			} else if($givenData['circleType']=="CarPool"){
				// Call Helper function
				$this->getCarpoolVehivles($givenData);
			}else{
				$source_lat_long = $this->GetLatLongFromAddress($givenData['source_address']);
				$source_lat_long_arr = explode('|', $source_lat_long);
				$dLatitude=$source_lat_long_arr[0];
				$dLongitude=$source_lat_long_arr[1];
				$source_city_status_km = $this->webservices_model->getCityFromCustomerLatLongAsKM($dLatitude,$dLongitude);
				$source_city_status_mile = $this->webservices_model->getCityFromCustomerLatLongAsMile($dLatitude,$dLongitude);
				
				$check_km=($source_city_status_km['distance'] <= $source_city_status_km['fRadius']) ? $source_city_status_km : 0;
				$check_mile = ($source_city_status_mile['distance'] <= $source_city_status_mile['fRadius']) ? $source_city_status_mile : 0;
				if($check_km != 0 || $check_mile != 0){
					if($check_km != 0 && $check_mile == 0){
						$sourceCityDetail = $check_km;
					}else if($check_mile != 0 && $check_km == 0){
						$sourceCityDetail = $check_mile;
					}else if($check_mile != 0 && $check_km != 0){
						$distanceFromKM = $check_km['distance'];
						$distanceFromMile=$check_mile['distance']*1.609344;
						$sourceCityDetail = ($distanceFromKM < $distanceFromMile) ? $check_km : $check_mile ;
					}else{
						$data['msg'] = "Service Not Available For Selected Options";
						echo json_encode($data);exit;
					}
				}else{
					$data['msg'] = "Service Not Available For Selected Options";
					echo json_encode($data);exit;
				}
				// $sourceCityDetail = $this->webservices_model->get_one_city_details_byid(2338);
				// $this->printthis("sourceCityDetail");
				/*$this->printthis($sourceCityDetail);*/
				if($givenData['circleType']=="Taxi"){
					$ridetype = 'One Way';
					$destination_address = mysql_real_escape_string($givenData['destination_address']);
					$finishlatlong = $this->GetLatLongFromAddress($destination_address);
					// Check Local or OutStation
					$check_distance = $this->GetMileKMFromLatLong($sourceCityDetail['tCityLatLong'],$finishlatlong);
					if ($sourceCityDetail['eRadiusUnit']=='Miles') {
						$ridelocation = ($check_distance['miles']<=$sourceCityDetail['fRadius']) ? "Local" : "Outstation" ;
					} else {
						$ridelocation = ($check_distance['kms']<=$sourceCityDetail['fRadius']) ? "Local" : "Outstation" ;
					}
					if ($ridelocation=='Local') {
						$ridelocationArr[] = 'Local';
						$ridelocationArr[] = 'Both';
					} else if ($ridelocation=='Outstation') {
						$ridelocationArr[] = 'Outstation';
						$ridelocationArr[] = 'Both';
					}else{
						$ridelocationArr[] = 'Local';
						$ridelocationArr[] = 'Outstation';
						$ridelocationArr[] = 'Both';
					}
					// $ridelocation='Outstation';
					// $ridelocationArr[] = 'Outstation';
					// $ridelocationArr[] = 'Both';
				}else if($givenData['circleType']=="LocalByDuration"){
					$ridelocation = 'LocalByDuration';
					$ridetype = 'Round';
					$ridelocationArr[] = 'LocalByDuration';
					$ridelocationArr[] = 'BothByDuration';
				}else if($givenData['circleType']=="OutStationByDuration"){
					$ridelocation = 'OutStationByDuration';
					$ridetype = 'Round';
					$ridelocationArr[] = 'OutStationByDuration';
					$ridelocationArr[] = 'BothByDuration';
				}else if($givenData['circleType']=="Shuttle"){
					$ridelocation = 'Shuttle';
					$ridetype = 'One Way';
					$ridelocationArr[] = 'Shuttle';
				}else{
					$Data['msg'] = "Failure";
					echo json_encode($Data);
					exit;
				}
				$vehicleDetails = $this->webservices_model->get_vehicle_detail_by_fare($sourceCityDetail['iCityId'],$ridelocation,$ridetype);
				/*echo "<pre>";print_r($vehicleDetails);exit();*/
			}
		 	$check_array=array('LocalByDuration','OutStationByDuration');
			// Check available drivers for : now
			if ($givenData['booktype']=='now' and !in_array($givenData['circleType'], $check_array)) {
				$source_lat_long = explode('|', $sourceCityDetail['tCityLatLong']);
				$qurVar = ($sourceCityDetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
				
				$drivers = $this->webservices_model->get_all_drivers_within_city($source_lat_long[0],$source_lat_long[1],$sourceCityDetail['fRadius'],$qurVar,$ridelocationArr,'');
				$availableDriverCount = 0;
				$companyArr = array();
				foreach ($drivers as $drkey => $driver) {
					$countRunningTrip=$this->webservices_model->checkRunningTripByDriver($driver['iDriverId']);
					/*$this->printthis("countRunningTrip");*/
					/*$this->printthis($countRunningTrip);*/
					if($countRunningTrip==0){
						$availableDriverCount++;
						$vehicleCompany=$this->webservices_model->get_vehicle_by_model($driver['iModelId']);
						if (!in_array($vehicleCompany['iVehicleCompanyId'], $companyArr)) {
							$companyArr[]=$vehicleCompany['iVehicleCompanyId'];
						}
					}
				}

			}
			// prepare result arr
			if(count($vehicleDetails)>0){
				
				/*$this->printthis("vehicleDetails");
				$this->printthis($vehicleDetails);*/
				
				/*$this->printthis($vehicleDetails);*/
				$mainarr = array();
				for ($i=0; $i <count($vehicleDetails) ; $i++) {

					if ($givenData['booktype']=='later') {
						$tmp['iVehicleCompanyId']= $vehicleDetails[$i]['iVehicleCompanyId'];
						$tmp['vCompany']= $vehicleDetails[$i]['vCompany'];
						$tmp['eStatus']= $vehicleDetails[$i]['eStatus'];
						$base_path = $this->data['base_path'];
						$filepath=$base_path.'uploads/car/'.$vehicleDetails[$i]['iVehicleCompanyId'].'/'.$vehicleDetails[$i]['vCarimage'];
						
						if (file_exists($filepath)) {
							$tmp['image_url'] = $base_upload.'car/'.$vehicleDetails[$i]['iVehicleCompanyId'].'/'.$vehicleDetails[$i]['vCarimage'];
						}else {
							$tmp['image_url'] = $base_upload."No_image_available.jpg";  
						}
						$mainarr[]=$tmp;
						unset($tmp);
					}else if(in_array($givenData['circleType'], $check_array)){
						if ($givenData['booktype']=='now'){
							$tmp['iVehicleCompanyId']= $vehicleDetails[$i]['iVehicleCompanyId'];
							$tmp['vCompany']= $vehicleDetails[$i]['vCompany'];
							$tmp['eStatus']= $vehicleDetails[$i]['eStatus'];
							$base_path = $this->data['base_path'];
							$filepath=$base_path.'uploads/car/'.$vehicleDetails[$i]['iVehicleCompanyId'].'/'.$vehicleDetails[$i]['vCarimage'];
							
							if (file_exists($filepath)) {
								$tmp['image_url'] = $base_upload.'car/'.$vehicleDetails[$i]['iVehicleCompanyId'].'/'.$vehicleDetails[$i]['vCarimage'];
							}else {
								$tmp['image_url'] = $base_upload."No_image_available.jpg";  
							}
							$mainarr[]=$tmp;
							unset($tmp);
						}
					}else{
						if (($givenData['booktype']=='now' && in_array($vehicleDetails[$i]['iVehicleCompanyId'], $companyArr))){
							$tmp['iVehicleCompanyId']= $vehicleDetails[$i]['iVehicleCompanyId'];
							$tmp['vCompany']= $vehicleDetails[$i]['vCompany'];
							$tmp['eStatus']= $vehicleDetails[$i]['eStatus'];
							$base_path = $this->data['base_path'];
							$filepath=$base_path.'uploads/car/'.$vehicleDetails[$i]['iVehicleCompanyId'].'/'.$vehicleDetails[$i]['vCarimage'];
							
							if (file_exists($filepath)) {
								$tmp['image_url'] = $base_upload.'car/'.$vehicleDetails[$i]['iVehicleCompanyId'].'/'.$vehicleDetails[$i]['vCarimage'];
							}else {
								$tmp['image_url'] = $base_upload."No_image_available.jpg";  
							}
							$mainarr[]=$tmp;
							unset($tmp);
						}
					}
				}
				/*$this->printthis("mainarr");
				$this->printthis($mainarr);exit;*/
				if (count($mainarr)>0) {
					$Data['data'] = $mainarr; 
					$Data['msg'] = "Success";
				} else {
					$Data['msg'] = "Service Not Available For Selected Options";
				}
			}else{
				$Data['msg'] = "Service Not Available For Selected Options";
			}
		}else{
			$Data['msg'] = "Failure";
		}
		echo json_encode($Data);
		exit;
	}

	//helper function for getVehicleDetails as : carpool
	function getCarpoolVehivles($givenData){
		/* for CarPool : 
				source_address
				destination_address
				booktype [now / later]
				book_time
				eSmokingPreference ( No / Yes / Does Not Matter )
				eGenderPreference ( Male / Female / Does Not Matter )
		*/
		// $this->printthis('givenData');
		// $this->printthis($givenData);
		if ($givenData['source_address'] && $givenData['destination_address'] && $givenData['booktype'] && $givenData['eSmokingPreference'] && $givenData['eGenderPreference']){
			if ($givenData['booktype']=='later'){
				if ($givenData['book_time']){
					$searchDate=$givenData['book_time'];
				}else{
					$Data['msg'] = "Failure";
					echo json_encode($Data);
					exit;
				}
			}
			if($givenData['eSmokingPreference']=='Yes'){
				$smokingPref= array('Yes','Does Not Matter');
			}else if($givenData['eSmokingPreference']=='No'){
				$smokingPref= array('No');
			}else{
				$smokingPref= array('Yes','No','Does Not Matter');
			}

			if($givenData['eGenderPreference']=='Male'){
				$genderPref= array('Male','Does Not Matter');
			}else if($givenData['eGenderPreference']=='Female'){
				$genderPref= array('Female','Does Not Matter');
			}else{
				$genderPref= array('Male','Female','Does Not Matter');
			}
			// get source, dest city
			$source = $this->getCityFromLatLongAddress('Address',$givenData['source_address']);
			/*$this->printthis('source');
			$this->printthis($source);*/
			// $dest = $this->GetCityFromAddressOrLatLong($givenData['destination_address'],'address');
			$dest = $this->getCityFromLatLongAddress('Address',$givenData['destination_address']);
			/*$this->printthis('Dest');
			$this->printthis($dest);*/

			$sourceCityDetail = $this->webservices_model->get_one_city_details_byid($source['iCityId']);
			$destCityDetail = $this->webservices_model->get_one_city_details_byid($dest['iCityId']);
			/*$this->printthis('sourceCityDetail');
			$this->printthis($sourceCityDetail);
			$this->printthis('destCityDetail');
			$this->printthis($destCityDetail);*/
			// TimeZone
			if ($givenData['booktype']=='later'){
				$book_time = date('Y-m-d H:i:s', strtotime($givenData['book_time']));
			}else{
				$tmpDate = new DateTime("now", new DateTimeZone($sourceCityDetail['vTimeZone']));
				$book_time = $tmpDate->format('Y-m-d H:i:s');
			}
			$day = date('l', strtotime($book_time));
			$tdate = date('Y-m-d', strtotime($book_time));
			$ttime = date('H:i', strtotime($book_time));
			// echo "Trip Time : ".$ttime.", Trip Day : ".$day."\n";
			//----- New -----
			$slatlong = explode('|', $source['address_lat_long']);
			$flatlong = explode('|', $dest['address_lat_long']);
			
			$qurVarSource=($sourceCityDetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
			$qurVardest = ($destCityDetail['eRadiusUnit']=='KMs') ? 6371 : 3959;
			
			$check_distance = $this->GetMileKMFromLatLong($sourceCityDetail['tCityLatLong'],$dest['address_lat_long']);
			if ($sourceCityDetail['eRadiusUnit']=='Miles') {
				$ridelocation = ($check_distance['miles']<=$sourceCityDetail['fRadius']) ? "local" : "outstation" ;
			} else {
				$ridelocation = ($check_distance['kms']<=$sourceCityDetail['fRadius']) ? "local" : "outstation" ;
			}
			if ($ridelocation=='local') {
				$ridelocationArr[] = 'CarPoolLocal';
				$ridelocationArr[] = 'CarPoolBoth';
				$eFaretype = 'CarPoolLocal';
			} else {
				$ridelocationArr[] = 'CarPoolOutstation';
				$ridelocationArr[] = 'CarPoolBoth';
				$eFaretype = 'CarPoolOutstation';
			}

			$driversbySource = $this->webservices_model->getAllPoolDriversWithinCity($slatlong[0],$slatlong[1],$sourceCityDetail['fSourceRadius'], $qurVarSource,$ridelocationArr,'',$smokingPref,$genderPref);
			/*$this->printthis('driversbySource');
			$this->printthis($driversbySource);*/
			$voIds = $this->webservices_model->getVOfromCity($source['iCityId']);
			/*$this->printthis('voIds');
			$this->printthis($voIds);*/

			$voIdsArr= array();
			foreach ($voIds as $vokey => $voId) {
				$voIdsArr[]=$voId['iVehicleOwnerId'];
			}
			$iCarpoolIds= array();
			foreach ($driversbySource as $dskey => $drSource){
				if (in_array($drSource['iVehicleOwnerId'], $voIdsArr)) {
					// check fare available
					$isFare = $this->webservices_model->checkCarpoolFare($source['iCityId'],$eFaretype,$drSource['iVehicleCompanyId']);
					if ($isFare > 0 ) {
						$iCarpoolIds[]=$drSource['iCarpoolId'];
					}
				}
			}
			if (count($iCarpoolIds)==0) {
				$Data['msg'] = "Service Not Available For Selected Options";
				echo json_encode($Data);exit;
			}
			$driversbyDest = $this->webservices_model->getAllPoolDriversBySource($flatlong[0],$flatlong[1],$destCityDetail['fDestinationRadius'], $qurVardest,$ridelocationArr,'',$smokingPref,$genderPref,$iCarpoolIds);
			// $this->printthis('driversbySource');
			// $this->printthis($driversbySource);
			// $this->printthis('driversbyDest');
			// $this->printthis($driversbyDest);
			$driverArr= array();
			$allPools= array();
			if (count($driversbyDest) > 0) {
				foreach ($driversbyDest as $dskey => $drDest){
					$countRunningTrip = $this->webservices_model->checkRunningTripByDriver($drDest['iDriverId']);
					if($countRunningTrip==0){
						$driverArr[]=$drDest['iDriverId'];
						$allPools[]=$drDest;
					}
				}
			}
			// $this->printthis($allPools);
			$driverArr=array();
			$companyIdArr=array();
			$companyArr=array();
			foreach ($allPools as $pkey => $pool){
				$trimmed = trim($pool['vTimeGap'], '+-/');
				$from = date('H:i', strtotime($pool['dFromDate']."- ".$trimmed));
				$till = date('H:i', strtotime($pool['dFromDate']."+ ".$trimmed));
				// echo "Before : ".$pool['vTimeGap'].',   After : '.$trimmed.", From : ".$from.", to : ".$till."\n";
				if ($this->timeisBetween($from, $till, $ttime)) {
					if($pool['eType']=='Regular'){
						// Check Day
						$dayexist = $this->webservices_model->checkCarPoolDay($pool['iCarpoolId'],$day);
						// echo "for iCarpoolId : ".$pool['iCarpoolId']. " ==> Pool Day Exist : ".$dayexist."\n";
						// time compare
						if ($dayexist > 0 && !in_array($pool['iDriverId'], $driverArr)) {
							if ($this->timeisBetween($from, $till, $ttime)) {
								$driverArr[]=$pool['iDriverId'];
								if(!in_array($pool['iVehicleCompanyId'], $companyIdArr)){
									$companyIdArr[]=$pool['iVehicleCompanyId'];
									$tmp['iVehicleCompanyId']=$pool['iVehicleCompanyId'];
									$tmp['vCompany']=$pool['vCompany'];
									$tmp['eStatus']=$pool['eStatus'];
									$tmp['vCarimage']=$pool['vCarimage'];
									$companyArr[]=$tmp;
									unset($tmp);
								}
							}
						}
					} else {
						// check date 
						$pooldate = date('Y-m-d', strtotime($pool['dFromDate']));
						if ($pooldate==$tdate  && !in_array($pool['iDriverId'], $driverArr)) {
							if ($this->timeisBetween($from, $till, $ttime)) {
								$driverArr[]=$pool['iDriverId'];
								if(!in_array($pool['iVehicleCompanyId'], $companyIdArr)){
									$companyIdArr[]=$pool['iVehicleCompanyId'];
									$tmp['iVehicleCompanyId']=$pool['iVehicleCompanyId'];
									$tmp['vCompany']=$pool['vCompany'];
									$tmp['eStatus']=$pool['eStatus'];
									$tmp['vCarimage']=$pool['vCarimage'];
									$companyArr[]=$tmp;
									unset($tmp);
								}
							}
						}
					}
				}
			}
			// exit;
			$base_path = $this->data['base_path'];
			$base_upload=$this->data['base_upload'];
			foreach ($companyArr as $cmkey => $company){
				$filepath=$base_path.'uploads/car/'.$company['iVehicleCompanyId'].'/'.$company['vCarimage'];
				if (file_exists($filepath)) {
					$companyArr[$cmkey]['image_url'] = $base_upload.'car/'.$company['iVehicleCompanyId'].'/'.$company['vCarimage'];
				}else {
					$companyArr[$cmkey]['image_url'] = $base_upload."No_image_available.jpg";  
				}
				unset($companyArr[$cmkey]['vCarimage']);
			}
			/*
			{
	            "iVehicleCompanyId": "16",
	            "vCompany": "Lamborgini",
	            "eStatus": "Active",
	            "image_url": "http://onetouchcab.com/uploads/car/16/rsz2images.jpg"
	        }
			*/
			if (count($companyArr)>0) {
				$Data['data'] = $companyArr; 
				$Data['msg'] = "Success";
			} else {
				$Data['msg'] = "Service Not Available For Selected Options";
			}
		} else {
			$Data['msg'] = "Failure";
		}
		echo json_encode($Data);
		exit;
	}

	function getOwnerDriverList(){
		$iVehicleOwnerId = $this->input->post('iVehicleOwnerId');
		if($iVehicleOwnerId != ''){
			$owner_exists = $this->webservices_model->check_owner_exists($iVehicleOwnerId);
			if ($owner_exists=='exist') {
				// Get Details
				$driversArr = $this->webservices_model->allDriversByOwnerID($iVehicleOwnerId);
				$resArr = array();
				foreach ($driversArr as $dkey => $driver) {
					$tmpArr['iDriverId']=$driver['iDriverId'];
					$tmpArr['fullname']=$driver['vFirstName'].' '.$driver['vLastName'];
					$tmpArr['vEmail']=$driver['vEmail'];
					$tmpArr['eStatus']=$driver['eStatus'];
					$tmpArr['iMobileNo']=$driver['iMobileNo'];
					if($driver['vProfileImage']){
						$tmpArr['image_Url'] = $this->data['base_url'].'uploads/driver/'.$driver['iDriverId'].'/'.$driver['vProfileImage'];
					}else {
						$tmpArr['image_Url'] = $this->data['base_url'].'uploads/red-driver.png';
					}
					// Avg Rating
					$avgRating = $this->webservices_model->getDriverAverageRating($driver['iDriverId']);
					$tmpArr['average_rating'] = number_format($avgRating['fRating'],2);
					// vehicle detail
					$car_detail = $this->webservices_model->get_car_details_by_driverid($driver['iDriverId']);
					if ($car_detail) {
						$tmpArr['vCompany']=$car_detail['vCompany'];
						$tmpArr['vModelName']=$car_detail['vModelName'];
						$tmpArr['vRegistrationNo']=$car_detail['vRegistrationNo'];
						$tmpArr['vehicalAssignStatus']='Yes';
					} else {
						$tmpArr['vCompany']='';
						$tmpArr['vModelName']='';
						$tmpArr['vRegistrationNo']='';
						$tmpArr['vehicalAssignStatus']='No';
					}
					
					$tmpArr['eAvailability']=($driver['eAvailability']=='Both')?'Both (Local / Outstation)':$driver['eAvailability'];
					$licence_detail = $this->webservices_model->GetLicenceInfo($driver['iDriverId']);
					if($licence_detail){
						$tmpArr['licence_status']=$licence_detail['eVerifiedStatus'];
					}else{
						$tmpArr['licence_status']='';
					}
					$resArr[]=$tmpArr;
					unset($car_detail);
					unset($tmpArr);
				}

				if (!empty($resArr)){
					$data['data'] = $resArr;
					$data['msg'] = "Success";
				} else {
					$data['msg'] = "No Record Found";
				}
			} else {
				$data['msg'] = "Vehical Owner Not Exist";
			}
		}else{
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function driverLogin($vEmail,$vPassword){
		$base_upload=$this->data['base_upload'];
		if(($vEmail != '') && ($vPassword != '')){

			$status = $this->webservices_model->check_driver_exists_by_email($vEmail);
			if($status=='exist'){
				$login_status = $this->webservices_model->check_driver_login_status($vEmail);

				if($login_status=='No'){
					$res=$this->webservices_model->getVarifyOwner($vEmail);
					if($res['eApprovalStatus']=='Approved'){
						$driveremail = $this->webservices_model->check_Exist('vEmail',$vEmail,'driver');
						if($driveremail){
							$driverpassword = $this->webservices_model->check_password($vEmail,$vPassword,'driver');
							if($driverpassword){
								$driverInfo = $this->webservices_model->check_driver_auth($vEmail,$vPassword);
								if($driverInfo){
									$driverInfo['vPassword'] = $this->decrypt($driverInfo['vPassword']);
									$driverInfo['vDriverFullname'] = $driverInfo['vFirstName']." ".$driverInfo['vLastName'];

									if($driverInfo){
										if($driverInfo['eVerifiedMobile']=='Yes' && $driverInfo['eStatus']=='Active'){

											if ($driverInfo['iParentDriverId']==0) {
												if($driverInfo['vProfileImage']){
													$driverInfo['image_Url'] = $base_upload.'driver/'.$driverInfo['iDriverId'].'/'.$driverInfo['vProfileImage'];
												}
												else {
													$driverInfo['image_Url'] = $base_upload.'red-driver.png';
												}   
											}else{
												if($driverInfo['vProfileImage']){
													$driverInfo['image_Url'] = $base_upload.'subdriver/'.$driverInfo['iDriverId'].'/'.$driverInfo['vProfileImage'];
												}
												else {
													$driverInfo['image_Url'] = $base_upload.'red-driver.png';
												}
											}
											$change_status = $this->webservices_model->update_login_status($vEmail);
											$all_driver_trips = $this->webservices_model->get_all_driver_trips($driverInfo['iDriverId']);
											$total_driver_trips = count($all_driver_trips);
											$total_rating_cnt = 0;
											foreach ($all_driver_trips as $key => $value) {
												$total_rating_cnt = ($total_rating_cnt + $value['fRating']);
											}
											$driverInfo['average_rating'] = number_format(($total_rating_cnt/$total_driver_trips),2);
											$car_details = $this->webservices_model->get_car_details($driverInfo['iDriverId']);
											$driverInfo['vCompany'] = ($car_details['vCompany'])?$car_details['vCompany']:"";
											$driverInfo['vModelName'] = ($car_details['vModelName'])?$car_details['vModelName']:"";
											$driverInfo['vPlateNo'] = ($car_details['vRegistrationNo'])?$car_details['vRegistrationNo']:"";

											// Licence
											$licence = $this->webservices_model->GetLicenceInfo($driverInfo['iDriverId']);
											if($licence){
												$driverInfo['licence_status']=$licence['eVerifiedStatus'];
											}else{
												$driverInfo['licence_status']='';
											}
											if($driverInfo['vCountry']==''){
												$driverInfo['vCountry']='';
											}
											if($driverInfo['vState']==''){
												$driverInfo['vState']='';
											}
											$Data['data']= $driverInfo;
											$Data['msg'] = "Login Successfully";
											$Data['role'] = "driver";

										}else if ($driverInfo['eVerifiedMobile']=='No'){
											$driverdet['iOTP']=random_string($type = 'numeric', 4);	
											$driverdet['eVerifiedMobile']='No';
											$res = $this->webservices_model->update_driver_detail($driverdet,$driverInfo['iDriverId']);
											$smsText = 'Your OneTouchCab verification code is: '.$driverdet['iOTP'];
											require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
											$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
											$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
											$client = new Services_Twilio($AccountSid, $AuthToken);
											$extraChr = array(" ", "(", ")", "-", "[", "]");
											$update_rider_no = '+'.str_replace($extraChr, "", $driverInfo['iMobileNo']);
											try {
												$sms[] = $client->account->messages->sendMessage("+18562882821",$update_rider_no,$smsText);
											} catch (Exception $e) {
											   
											}
											$Data['msg'] = "Your Mobile No is not varified";
											$Data['iMobileNo'] = $driverInfo['iMobileNo'];
										}else{
											$Data['msg'] = "Your status isn't active";
										}
									}else{
										$Data['msg'] = "No Record Found";
									}
								}
							}else{
								$Data['msg'] = "Your password doesn't match.";  
							}   
						}else{
							$Data['msg'] = "Your email doesn't match.";
						}
					}else if($res['eApprovalStatus']=='Pending'){
						$Data['msg'] = "Owner pending site admin approval. Please contact for further assistance.";
					}else if($res['eApprovalStatus']=='Rejected'){
						$Data['msg'] = "Owner account is rejected by site admin. Please contact for further assistance.";
					}
					
				}else {
					$Data['msg'] = "Driver seems to be already logged In on Another Device";
				}
			}else {
				$Data['msg'] = "Your email doesn't match.";    
			}
		}
		else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function autodriverLogin($id){
		$base_upload=$this->data['base_upload'];
		if($id){
			$DriverInfo = $this->webservices_model->checkDriverExist($id);
			if ($DriverInfo) {
				$res=$this->webservices_model->getVarifyOwner($DriverInfo['vEmail']);
				if($res['eApprovalStatus']=='Approved'){
					$driverDetail['vDriverFullname'] = $DriverInfo['vFirstName']." ".$DriverInfo['vLastName'];
					$driverDetail['tAddress'] = $DriverInfo['tAddress'];
					$driverDetail['vBusinessNumber'] = $DriverInfo['vBusinessNumber'];
					$driverDetail['dRegisterDate'] = $DriverInfo['dRegisterDate'];
					$driverDetail['vPostalCode'] = $DriverInfo['vZipcode'];
					$driverDetail['iMobileNo'] = $DriverInfo['iMobileNo'];
					$driverDetail['vEmail'] = $DriverInfo['vEmail'];
					$driverDetail['vPassword'] = $DriverInfo['vPassword'];
					$driverDetail['iCountryId'] = $DriverInfo['iCountryId'];
					$driverDetail['vCountry'] = $DriverInfo['vCountry'];
					$driverDetail['iStateId'] = $DriverInfo['iStateId'];
					$driverDetail['vState'] = $DriverInfo['vState'];
					$driverDetail['iCityId'] = $DriverInfo['iCityId'];
					$driverDetail['vCity'] = $DriverInfo['vCity'];
					$driverDetail['eAvailability'] = $DriverInfo['eAvailability'];
					$driverDetail['eOutOfService'] = $DriverInfo['eOutOfService'];
					if($DriverInfo['vProfileImage']){
						$driverDetail['image_Url'] = $base_upload.'driver/'.$DriverInfo['iDriverId'].'/'.$DriverInfo['vProfileImage'];
					}
					else {
						$driverDetail['image_Url'] = $base_upload.'red-driver.png';
					}
					$all_driver_trips = $this->webservices_model->get_all_driver_trips($DriverInfo['iDriverId']);
					$total_driver_trips = count($all_driver_trips);
					$total_rating_cnt = 0;
					foreach ($all_driver_trips as $key => $value) {
						$total_rating_cnt = ($total_rating_cnt + $value['fRating']);
					}
					$driverDetail['average_rating'] = number_format(($total_rating_cnt/$total_driver_trips),2);

					$car_details = $this->webservices_model->get_car_details($DriverInfo['iDriverId']);
					if(!empty($car_details)){
						$driverDetail['vCompany'] = $car_details['vCompany'];
						$driverDetail['vModelName'] = $car_details['vModelName'];
						$driverDetail['vPlateNo'] = $car_details['vRegistrationNo'];
					}
					// Licence
					$licence = $this->webservices_model->GetLicenceInfo($DriverInfo['iDriverId']);
					if($licence){
						$driverDetail['licence_status']=$licence['eVerifiedStatus'];
					}else{
						$driverDetail['licence_status']='';
					}
					$Data['data']= $driverDetail;
					$Data['msg'] = "Login Successfully";
					$Data['role'] = "driver";
				}else if($res['eApprovalStatus']=='Pending')
				{
					$Data['msg'] = "Owner pending site admin approval. Please contact for further assistance.";
				}else if($res['eApprovalStatus']=='Rejected'){
					$Data['msg'] = "Owner account is rejected by site admin. Please contact for further assistance.";
				}	
			}else{
				$Data['msg'] = "No Record Found";
			}
		}else{
			$Data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function addDriverByOwner(){
		if($this->input->post('iVehicleOwnerId') && $this->input->post('vFirstName') && $this->input->post('vLastName') && $this->input->post('eGender') && $this->input->post('vEmail')&& $this->input->post('tAddress') && $this->input->post('iCountryId') && $this->input->post('iStateId') && $this->input->post('vZipcode') && $this->input->post('iMobileNo1') && $this->input->post('eAvailability')){

			$vEmail=$this->input->post('vEmail');
			$check_exist = $this->webservices_model->check_email_exists('driver', $vEmail);
			$check_exist1 = $this->webservices_model->check_email_exists('client', $vEmail);
			if ($check_exist == 0 && $check_exist1 == 0){
				$iMobileNo = $this->input->post('iMobileNo1');

				$mobileVarify = $this->webservices_model->getVarifyDriverNo($iMobileNo,$this->input->post('iVehicleOwnerId'),'');
				if($mobileVarify=='Mobexists'){
					$data['msg'] = "Mobile Number Exist";
					header('Content-type: application/json');
					$main = json_encode($data);
					echo $main;
					exit; 
				}
				$new_data['iVehicleOwnerId']=$this->input->post('iVehicleOwnerId');
				$new_data['vFirstName']=$this->input->post('vFirstName');
				$new_data['vLastName']=$this->input->post('vLastName');
				$new_data['eGender']=$this->input->post('eGender');
				$new_data['vEmail']=$vEmail;
				$new_data['vBusinessName']=$this->input->post('vBusinessName');
				$new_data['tAddress']=$this->input->post('tAddress');
				$new_data['iCountryId']=$this->input->post('iCountryId');
				$new_data['iStateId']=$this->input->post('iStateId');
				$new_data['iCityId']=$this->input->post('iCityId');
				$new_data['vCity']=$this->input->post('vCity');
				$new_data['vZipcode']=$this->input->post('vZipcode');
				$new_data['iMobileNo1']=$this->input->post('iMobileNo1');
				if ($this->input->post('eAvailability')=='Both (Local / Outstation)') {
					$new_data['eAvailability']='Both';
				}else{
					$new_data['eAvailability']=$this->input->post('eAvailability');
				}
				$new_data['dRegisterDate'] = date('Y-m-d');
				$new_data['vActivationCode'] = random_string('alnum',8);
				$new_data['vForgotPasswordString'] = random_string('alnum',8);
				$new_data['eStatus'] = 'Inactive';
				$password=random_string('alnum',8);
				$new_data['vPassword'] = $this->encrypt($password);
				if ($new_data['eAvailability']=='CarPoolLocal' || $new_data['eAvailability']=='CarPoolOutstation' || $new_data['eAvailability']=='CarPoolBoth') {
					if ($this->input->post('eSmokingPreference') && $this->input->post('eGenderPreference')) {
						$new_data['eSmokingPreference']=$this->input->post('eSmokingPreference');
						$new_data['eGenderPreference']=$this->input->post('eGenderPreference');
					} else {
						$data['msg'] = "Failure";
						header('Content-type: application/json');
						echo json_encode($data);
						exit;
					}
				}
				// Save data
				$iDriverId = $this->webservices_model->save_data($new_data,'driver');
				if ($_FILES['vProfileImage']['name'] != '') {
					$clean_name = $this->clean($_FILES['vProfileImage']['name']);
					$img_uploaded = $this->do_upload_driver_profile_photo($iDriverId,$clean_name,'vProfileImage');
					$res = $this->webservices_model->update_driver_detail(array('vProfileImage' => $img_uploaded),$iDriverId);
				}
				$driverdet['iOTP']=random_string($type = 'numeric', 4);	
				$driverdet['eVerifiedMobile']='No';
				$res = $this->webservices_model->update_driver_detail($driverdet,$iDriverId);
				/*$smsText = 'Your OneTouchCab verification code is: '.$driverdet['iOTP'];
				require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
				$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
				$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
				$client = new Services_Twilio($AccountSid, $AuthToken);
				$extraChr = array(" ", "(", ")", "-", "[", "]");
				$update_rider_no = '+'.str_replace($extraChr, "", $new_data['iMobileNo1']);
				try {
					$sms[] = $client->account->messages->sendMessage("+18562882821",$update_rider_no,$smsText);
				} catch (Exception $e) {
				    $data['msg'] = "SMS Sending Failure";
				}*/
				$data['msg'] = "Success";
				$link=$this->data['site_url'].'login/confirm_email?code='.$new_data['vActivationCode'];
				$siteurl = $this->config->item('base_url');
				$MailFooter = $this->data['MAIL_FOOTER'];
				$adminEmailId = $this->data['EMAIL_ADMIN'];
				$siteName = $this->data['SITE_NAME'];
				$Email = $new_data['vEmail'];
				$FirstName = ucfirst($new_data['vFirstName']);
				$LastName = ucfirst($new_data['vLastName']);
				$name = $new_data['vFirstName'].' '.$new_data['vLastName'];
				
				$image = $siteurl.'assets/admin/image/onetouchtruck-logo.png';
				$bodyArr = array("#NAME#", "#PASSWORD#", "#EMAIL#", "#SITEURL#", "#MAILFOOTER#", "#SITE_NAME#", "#LINK#", "#FIRSTNAME#", "#LASTNAME#", "#IMAGE_URL#");
				$postArr = array($name, $password, $Email, $siteurl, $MailFooter, $siteName, $link, $FirstName, $LastName, $image);
				$sendDriver = $this->Send("CREATE_DRIVER_BY_OWNER", "Driver", $Email, $bodyArr, $postArr);
			} else {
				$data['msg'] = "Email Address Exist";
			}
		}else{
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function updateDriverDetail(){
		if($this->input->post('iDriverId')){
			$iDriverId = $this->input->post('iDriverId');
			if($this->input->post('fullname')){
				$fullname = explode(" ",$this->input->post('fullname'));
				$user['vFirstName'] = $fullname[0];
				if ($fullname[1]) {
					$user['vLastName'] = $fullname[1];
				}
			}

			if($this->input->post('eGender')){
				$user['eGender']=$this->input->post('eGender');   
			}

			if($this->input->post('eAvailability')){
				$user['eAvailability']=$this->input->post('eAvailability');   
			}

			if($this->input->post('eAvailability')){
				$user['eAvailability']=$this->input->post('eAvailability');
				if ($user['eAvailability']=='CarPoolLocal' || $user['eAvailability']=='CarPoolOutstation' || $user['eAvailability']=='CarPoolBoth'){
					if ($this->input->post('eSmokingPreference') && $this->input->post('eGenderPreference')) {
						$user['eSmokingPreference']=$this->input->post('eSmokingPreference');
						$user['eGenderPreference']=$this->input->post('eGenderPreference');
					} else {
						$data['msg'] = "Failure";
						header('Content-type: application/json');
						echo json_encode($data);
						exit;
					}
				}
			}

			if($this->input->post('tAddress')){
				$user['tAddress']=$this->input->post('tAddress');   
			}

			if($this->input->post('iCountryId')){
				$user['iCountryId']=$this->input->post('iCountryId');   
			}

			if($this->input->post('iStateId')){
				$user['iStateId']=$this->input->post('iStateId');   
			}

			if($this->input->post('iCityId')){
				$user['iCityId']=$this->input->post('iCityId'); 
			}

			if($this->input->post('vCity')){
				$user['vCity']=$this->input->post('vCity'); 
			}

			if($this->input->post('iMobileNo')){
				$user['iMobileNo1']=$this->input->post('iMobileNo'); 
			}
			
			if($this->input->post('vEmail')){
				$user['vEmail']=$this->input->post('vEmail');   
			}

			if($this->input->post('vPostalCode')){
				$user['vZipcode']=$this->input->post('vPostalCode'); 
			}
			$user['vCountryMobileCode'] = '+1';

			if($_FILES['vProfileImage']['name']!=''){


				$deletepath = $this->data['base_path'].'uploads/driver/'.$iDriverId.'/*';

				$files = glob($deletepath);

				foreach($files as $file)
				{ 
					if(is_file($file))
					unlink($file);
				}
				
				$user['vProfileImage'] = $this->clean($_FILES['vProfileImage']['name']);             
				$fieldname = 'vProfileImage';
				
				$img_uploaded_leads = $this->do_upload_driver_profile_photo($iDriverId,$user['vProfileImage'],
					$fieldname);
				//$user['vProfileImage'] = $image_uploaded ;
			}
			$base_url = $this->config->item('base_url');
			$totalaffectrows = $this->webservices_model->update_driver_detail($user,$iDriverId);
			
			$driverDetail = $this->webservices_model->get_driver_details_by_id($iDriverId);
			$driverDetail['iMobileNo']=$driverDetail['iMobileNo1'];
			
			$driverDetail['vPassword'] = $this->decrypt($driverDetail['vPassword']);
			
			$driverDetail['vDriverFullname'] = $driverDetail['vFirstName'].' '.$driverDetail['vLastName'];
			if($driverDetail){
				// Rating
				$all_driver_trips = $this->webservices_model->get_all_driver_trips($iDriverId);
				$total_driver_trips = count($all_driver_trips);
				$total_rating_cnt = 0;
				foreach ($all_driver_trips as $key => $value) {
					$total_rating_cnt = ($total_rating_cnt + $value['fRating']);
				}
				$driverDetail['average_rating'] = number_format(($total_rating_cnt/$total_driver_trips),2);

				if($driverDetail['vProfileImage']){
					$driverDetail['image_Url'] = $base_url.'uploads/driver/'.$iDriverId.'/'.$driverDetail['vProfileImage'];
				}
				else {
					$driverDetail['image_Url'] = $base_url.'uploads/red-driver.png';
				}
				
				$Data['data'] = $driverDetail;
				$Data['msg'] = "Update Success";
			}else{
				$Data['msg'] = "Update Fail";
			}
		}
		else {
			$Data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	function autoriderLogin($id,$latitude,$longitude){
		$base_upload=$this->data['base_upload'];
		if($id){
			$riderInfo = $this->webservices_model->checkRiderExistOrNot($id);
			if ($riderInfo) {
				$getClientDetail = $this->webservices_model->getClientDetailbyId($riderInfo['iClientId']);
				$base_path = $this->data['base_path'];
				$file_path = $base_path.'uploads/client/'.$riderInfo['iClientId'].'/'.$riderInfo['vProfileImage'];
				if($riderInfo['eImageType']=='withurl'){
					$riderDetails['image_Url'] = $riderInfo['vProfileImage'];
				}else if($riderInfo['eImageType']=='withouturl'){
					if (file_exists($file_path)) {
						$riderDetails['image_Url'] = $base_upload.'client/'.$riderInfo['iClientId'].'/'.$riderInfo['vProfileImage'];
					}else{
						$riderDetails['image_Url'] = $base_upload.'plash-holder.png';
					}
				}else{
					$riderDetails['image_Url'] = $base_upload.'plash-holder.png';
				}

				$riderDetails['fullname'] = $riderInfo['vFirstName'].' '.$riderInfo['vLastName'];
				if ($riderInfo['vFirstName'] && $riderInfo['vLastName']) {
					$riderDetails['vFirstName'] = $riderInfo['vFirstName'];
					$riderDetails['vLastName'] = $riderInfo['vLastName'];
				}
				if(isset($riderInfo['tAddress'])){
					$riderDetails['tAddress'] = $riderInfo['tAddress'];
				}
				else {
					$riderDetails['tAddress'] = "";
				}
				$riderDetails['vEmail'] = $riderInfo['vEmail'];
				$riderDetails['iMobileNo'] = $riderInfo['iMobileNo'];
				$riderDetails['eStatus'] = $riderInfo['eStatus'];
				$riderDetails['eGender'] = $riderInfo['eGender'];
				$riderDetails['vProfileImage'] = $riderInfo['vProfileImage'];
				$riderDetails['vPassword'] = $riderInfo['vPassword'];
				$riderDetails['image_Url'] = $riderDetails['image_Url'];
				$riderDetails['vPromotionCode'] = ($riderInfo['vPromotionCode'])?$riderInfo['vPromotionCode']:"";
				$riderDetails['vPostalCode'] = ($getClientDetail['vPostalCode'])?$getClientDetail['vPostalCode']:"";
				$riderDetails['iStateId'] = ($getClientDetail['iStateId'])?$getClientDetail['iStateId']:"";
				$riderDetails['vState'] = ($getClientDetail['vState'])?$getClientDetail['vState']:"";
				$riderDetails['iCityId'] = ($getClientDetail['iCityId'])?$getClientDetail['iCityId']:"";
				$riderDetails['vCity'] = ($getClientDetail['vCity'])?$getClientDetail['vCity']:"";
				// Current country for United States,Canada Show only Taxi, Car Pool, shuttle else Show All
				if ($this->data['SHOWALLTYPE']=='Yes') {
					$riderDetails['CurrentCountry']="ShowAll";
				} else {
					$client_country =$this->GetCountryFromAddressOrLatLong($latitude.','.$longitude,'latlong');
					$riderDetails['CurrentCountry']=$client_country['long_name'];
				}
				$Data['data'] = $riderDetails;
				$Data['msg']  = "Login Successfully";
				$Data['role'] = "rider";
			}else{
				$Data['msg'] = "No Record Found";
			}
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		exit;
	}

	function updateDriverByOwner(){
		if($this->input->post('iVehicleOwnerId') && $this->input->post('iDriverId')){
			$iDriverId = $this->input->post('iDriverId');   

			if($this->input->post('vFirstName')){
				$new_data['vFirstName'] = $this->input->post('vFirstName');
			}
			if($this->input->post('vLastName')){
				$new_data['vLastName'] = $this->input->post('vLastName');
			}
			if($this->input->post('eGender')){
				$new_data['eGender']=$this->input->post('eGender');
			}
			if($this->input->post('tAddress')){
				$new_data['tAddress']=$this->input->post('tAddress');
			}
			if($this->input->post('vBusinessName')){
				$new_data['vBusinessName']=$this->input->post('vBusinessName');
			}
			if($this->input->post('iCountryId')){
				$new_data['iCountryId']=$this->input->post('iCountryId');
			}
			if($this->input->post('iStateId')){
				$new_data['iStateId']=$this->input->post('iStateId');   
			}
			if($this->input->post('iCityId')){
				$new_data['iCityId']=$this->input->post('iCityId'); 
			}
			if($this->input->post('vCity')){
				$new_data['vCity']=$this->input->post('vCity'); 
			}
			if($this->input->post('vZipcode')){
				$new_data['vZipcode']=$this->input->post('vZipcode');
			}
			if($this->input->post('iMobileNo1')){
				$new_data['iMobileNo1']=$this->input->post('iMobileNo1'); 
			}
			if($this->input->post('eAvailability')){
				if ($this->input->post('eAvailability')=='Both (Local / Outstation)') {
					$new_data['eAvailability']='Both';
				}else{
					$new_data['eAvailability']=$this->input->post('eAvailability');
				}
				
				if ($new_data['eAvailability']=='CarPoolLocal' || $new_data['eAvailability']=='CarPoolOutstation' || $new_data['eAvailability']=='CarPoolBoth'){
					if ($this->input->post('eSmokingPreference') && $this->input->post('eGenderPreference')) {
						$new_data['eSmokingPreference']=$this->input->post('eSmokingPreference');
						$new_data['eGenderPreference']=$this->input->post('eGenderPreference');
					} else {
						$data['msg'] = "Failure";
						header('Content-type: application/json');
						echo json_encode($data);
						exit;
					}
				}
			}
			if($this->input->post('vPassword') !=''){
				$new_data['vPassword'] = $this->encrypt($this->input->post('vPassword'));
			}
			if($_FILES['vProfileImage']['name']!=''){
				$deletepath = $this->data['base_path'].'uploads/driver/'.$iDriverId.'/*';
				$files = glob($deletepath);
				foreach($files as $file){ 
					if(is_file($file))
					unlink($file);
				}
				$clean_name = $this->clean($_FILES['vProfileImage']['name']);
				$new_data['vProfileImage'] = $this->do_upload_driver_profile_photo($iDriverId,$clean_name,'vProfileImage');
			}
			
			$totalaffectrows = $this->webservices_model->update_driver_detail($new_data,$iDriverId);
			$data['msg'] = "Update Success";

		}else {
			$data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback']))
		{
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function GetCreditCardDetailsByClientId(){
		$this->load->helper('string');
		$base_url = $this->data['base_url'].'uploads/';
		$base_path = $this->data['base_path'].'uploads/';

		if ($_REQUEST['iClientId']) {
			$iClientId = $_REQUEST['iClientId'];
			$getCreditCardInfo = $this->webservices_model->GetAllCreditCardDetailsByClientId($iClientId);
			// echo '<pre>';print_r($getCreditCardInfo);exit;
			if($_REQUEST['iCityId']){
				
				if(count($getCreditCardInfo)>0){
					$getCityInfo = $this->webservices_model->get_city_currency($_REQUEST['iCityId']);
					if($getCityInfo){
						// Check Card
						$resultArr = array();
						foreach ($getCreditCardInfo as $key => $cardInfo){
							$cc_no = $this->decrypt_text($cardInfo['vCreditcardNo']);
							$cardStatus = $this->validateCard($cc_no,$cardInfo['iMonth'],$cardInfo['iYear'],$getCityInfo['vCurrencyCode']);
							if ($cardStatus=='Valid Card') {
								$tmpArr =  $cardInfo;
								$tmpArr['vCreditcardNo'] = "XXXX XXXX XXXX ".substr($cc_no,-4,4);
								$resultArr[]=$tmpArr;
							}
						}
						if(count($resultArr)>0){
							$Data['data'] = $resultArr;
							$Data['status'] = 'Credit Card';
							$Data['msg'] = "Success";
						}else {
							$Data['msg'] = "Not Any Valid Card Available";   
						}
					} else {
						$Data['msg'] = "Failure";
					}
				}else{
					$Data['msg'] = "Please update your Credit Card detail";
				}	
			}else{
				for ($i=0; $i < count($getCreditCardInfo); $i++) { 
					$credit_card_origin_no = $this->decrypt_text($getCreditCardInfo[$i]['vCreditcardNo']);
					$getCreditCardInfo[$i]['vCreditcardNo'] = "XXXX XXXX XXXX ".substr($credit_card_origin_no,-4,4);
					if ($getCreditCardInfo[$i]['vCardImage']) {
						$imagePath = $base_path.'credit_card'.'/'.$getCreditCardInfo[$i]['vCardImage'];
						if(file_exists($imagePath)){
							$getCreditCardInfo[$i]['vCardImage']= $base_url.'credit_card/'.$getCreditCardInfo[$i]['vCardImage'];
						}else{
							$getCreditCardInfo[$i]['vCardImage'] = $base_url.'No_image_available.jpg';
						}   
					}else{
						$getCreditCardInfo[$i]['vCardImage'] = $base_url.'No_image_available.jpg';
					}
				}
				if ($getCreditCardInfo) {
					if(count($getCreditCardInfo)>0){
						$Data['data'] = $getCreditCardInfo;
						$Data['status'] = 'Credit Card';
						$Data['msg'] = "Success";
					}else {
						$Data['msg'] = "Failure";   
					}
				}else{
					$Data['msg'] = "Failure";
				}
			}
		}else{
			$Data['msg'] = "Failure";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($Data);
		echo $callback . ''.$main.'';
		//return $callback . ''.$main.'';
		exit;
	}

	// Helper function to check card is valid or not using stripe
	function validateCard($vCreditcardNo,$exp_month,$exp_year,$currency){
		require_once 'stripe/init.php';
		$api_key = $this->fetch_stripe_account_api_key();
		\Stripe\Stripe::setApiKey($api_key);
		$myCard = array('number' => $vCreditcardNo, 'exp_month' => $exp_month, 'exp_year' => $exp_year);
		$amt = 100;// Sample amount
		try {
			// $charge = \Stripe\Charge::create(array('card' => $myCard, 'amount' => $amt, 'currency' => $currency, "capture" => false) );
			$charge = \Stripe\Charge::create(array('card' => $myCard, 'amount' => $amt, 'currency' => "USD", "capture" => false) );
			$cardStatus = "Valid Card";
			// echo "charge : <pre>";print_r($charge);echo "</pre>";exit;
		} catch(\Stripe\Error\Card $e) {
			// Since it's a decline, \Stripe\Error\Card will be caught
			$cardStatus = "Invalid Card";
		} catch (\Stripe\Error\RateLimit $e) {
			// Too many requests made to the API too quickly
			$cardStatus = "Invalid Card";
		} catch (\Stripe\Error\InvalidRequest $e) {
			// Invalid parameters were supplied to Stripe's API
			$cardStatus = "Invalid Card";
		} catch (\Stripe\Error\Authentication $e) {
		  // Authentication with Stripe's API failed
			$cardStatus = "Invalid Card";
		} catch (\Stripe\Error\ApiConnection $e) {
			$cardStatus = "Invalid Card";
		} catch (\Stripe\Error\Base $e) {
			$cardStatus = "Invalid Card";
		} catch (Exception $e) {
			$cardStatus = "Invalid Card";
		}
		return $cardStatus;
	}

	function GetAllAvailableTripByDriver(){
		if($this->input->post('iDriverId') && $this->input->post('type')){
			$driver_id = $this->input->post('iDriverId');
			$type = $this->input->post('type');
			// "vDestinationLocation_Longitude":
			$status = $this->webservices_model->check_driver_exists($driver_id);
			if($status=='exist'){
				$driverInfo = $this->webservices_model->get_driver_details($driver_id);
				
				if($type=='all'){
					$all_trips_details = $this->webservices_model->get_all_upcoming_complete_trip_details_by_driver_id($driver_id);
					foreach ($all_trips_details as $key => $value) {

						if($value['ePaymentType']=='Credit Card'){
							$card_id = $value['iCustomerCreditCardId'];
							$all_card_info = $this->webservices_model->get_card_info($card_id);
							$credit_card_no = $this->decrypt_text($all_card_info['vCreditcardNo']);
							$all_trips_details[$key]['vCreditcardNo'] = "XXXX XXXX XXXX ".substr($credit_card_no,-4,4); 
						}
						if($value['eTripLocation']=='OutStationByDuration'){

							$all_trips_details[$key]['fMinimumKm']=$this->webservices_model->getOutstationBydurationMinkmMiles($value['iVehicleCompanyId'],$value['iCityId']);
						}else{
							$all_trips_details[$key]['fMinimumKm']='';
						}

						$all_trips_details[$key]['vClientFullName'] = $value['vFirstName'].' '.$value['vLastName'];
						$all_trips_details[$key]['fDistance'] = $value['fDistance']." ".$value['eDistanceUnit'];
						unset($all_trips_details[$key]['eDistanceUnit']);
						$finalepayment = number_format($value['fFinalPayment'],2);

						$all_trips_details[$key]['fFinalPayment'] = $value['vCurrencySymbol'].$finalepayment;
						$value['dTripDate'] = date_create($value['dTripDate']);
						$all_trips_details[$key]['dTripDate'] = date_format($value['dTripDate'], 'jS F Y g:i A');

						$startpointlatlonarr = explode('|', $value['tPickUpAddressLatLong']);
						$all_trips_details[$key]['vPickupLocation_Latitude'] = $startpointlatlonarr[0];
						$all_trips_details[$key]['vPickupLocation_Longitude'] = $startpointlatlonarr[1];
						unset($all_trips_details[$key]['tPickUpAddressLatLong']);

						if ($value['tDestinationAddressLatLong']!='') {
							$finishpointlatlonarr = explode('|', $value['tDestinationAddressLatLong']);
							$all_trips_details[$key]['vDestinationLocation_Latitude'] = $finishpointlatlonarr[0];
							$all_trips_details[$key]['vDestinationLocation_Longitude'] = $finishpointlatlonarr[1];
						} else {
							$all_trips_details[$key]['vDestinationLocation_Latitude'] = '';
							$all_trips_details[$key]['vDestinationLocation_Longitude'] = '';
						}
						unset($all_trips_details[$key]['tDestinationAddressLatLong']);

						$all_trips_details[$key]['Customer_Current_Latitude'] = $value['dLatitude'];
						$all_trips_details[$key]['Customer_Current_Longitude'] = $value['dLongitude'];
						unset($all_trips_details[$key]['dLatitude']);
						unset($all_trips_details[$key]['dLongitude']);                         

						$all_trips_details[$key]['dTripDate'] = date_format($value['dTripDate'], 'jS F Y g:i A');
						$dToDate = date_create($value['dToDate']);
						$all_trips_details[$key]['dToDate']=($value['dToDate']!= '0000-00-00 00:00:00') ? date_format($dToDate, 'jS F Y g:i A') : '' ;
						$all_trips_details[$key]['vRoundOption'] = $value['vRoundOption'];

						if($value['eStatus']=='Complete'){
							$all_trips_details[$key]['eStatus'] = 'Completed';
						}
						else if($value['eStatus']=='Cancel'){
							$all_trips_details[$key]['eStatus'] = 'Cancelled';
						}
						$all_trips_details[$key]['rideFeedback'] = $this->webservices_model->getRideFeedback($all_trips_details[$key]['iTripId']);
						$all_trips_details[$key]['rideFeedbackArray'] = array();
						if (!empty($all_trips_details[$key]['rideFeedback'])) {
							$all_trips_details[$key]['rideFeedbackArray'][] = $all_trips_details[$key]['rideFeedback'];
						}
						
					}
				}
				else if($type=='Complete'){
					$all_trips_details = $this->webservices_model->get_all_complete_trip_details_by_driver_id($driver_id,$type);
					foreach ($all_trips_details as $key => $value) {


						if($value['eTripLocation']=='OutStationByDuration'){
							$all_trips_details[$key]['fMinimumKm']=$this->webservices_model->getOutstationBydurationMinkmMiles($value['iVehicleCompanyId'],$value['iCityId']);
						}else{
							$all_trips_details[$key]['fMinimumKm']='';
						}


						if($value['ePaymentType']=='Credit Card'){
							$card_id = $value['iCustomerCreditCardId'];
							$all_card_info = $this->webservices_model->get_card_info($card_id);
							$credit_card_no = $all_card_info['vCreditcardNo'];
							$all_trips_details[$key]['vCreditcardNo'] = "XXXX XXXX XXXX ".substr($credit_card_no,-4,4); 
						}

						$all_trips_details[$key]['vClientFullName'] = $value['vFirstName'].' '.$value['vLastName'];

						$all_trips_details[$key]['fDistance'] = $value['fDistance']." ".$value['eDistanceUnit'];
						unset($all_trips_details[$key]['eDistanceUnit']);
						$finalepayment = number_format($value['fFinalPayment'],2);
						$all_trips_details[$key]['fFinalPayment'] = $value['vCurrencySymbol'].$finalepayment;
						$value['dTripDate'] = date_create($value['dTripDate']);
						$all_trips_details[$key]['dTripDate'] = date_format($value['dTripDate'], 'jS F Y g:i A');

						$dToDate = date_create($value['dToDate']);
						$all_trips_details[$key]['dToDate']=($value['dToDate']!= '0000-00-00 00:00:00') ? date_format($dToDate, 'jS F Y g:i A') : '' ;
						$all_trips_details[$key]['vRoundOption'] = $value['vRoundOption'];

						$startpointlatlonarr = explode('|', $value['tPickUpAddressLatLong']);
						$all_trips_details[$key]['vPickupLocation_Latitude'] = $startpointlatlonarr[0];
						$all_trips_details[$key]['vPickupLocation_Longitude'] = $startpointlatlonarr[1];
						unset($all_trips_details[$key]['tPickUpAddressLatLong']);

						if ($value['tDestinationAddressLatLong']!='') {
							$finishpointlatlonarr = explode('|', $value['tDestinationAddressLatLong']);
							$all_trips_details[$key]['vDestinationLocation_Latitude'] = $finishpointlatlonarr[0];
							$all_trips_details[$key]['vDestinationLocation_Longitude'] = $finishpointlatlonarr[1];
						} else {
							$all_trips_details[$key]['vDestinationLocation_Latitude'] = '';
							$all_trips_details[$key]['vDestinationLocation_Longitude'] = '';
						}
						unset($all_trips_details[$key]['tDestinationAddressLatLong']);

						$all_trips_details[$key]['Customer_Current_Latitude'] = $value['dLatitude'];
						$all_trips_details[$key]['Customer_Current_Longitude'] = $value['dLongitude'];
						unset($all_trips_details[$key]['dLatitude']);
						unset($all_trips_details[$key]['dLongitude']);

						if($value['eStatus']=='Complete'){
							$all_trips_details[$key]['eStatus'] = 'Completed';
						}
						else if($value['eStatus']=='Cancel'){
							$all_trips_details[$key]['eStatus'] = 'Cancelled';
						}
						$all_trips_details[$key]['rideFeedback'] = $this->webservices_model->getRideFeedback($all_trips_details[$key]['iTripId']);
						$all_trips_details[$key]['rideFeedbackArray'] = array();
						if (!empty($all_trips_details[$key]['rideFeedback'])) {
							$all_trips_details[$key]['rideFeedbackArray'][] = $all_trips_details[$key]['rideFeedback'];
						}
					}
				}
				else if($type=='Pending'){
					$all_trips_details= $this->webservices_model->get_all_pending_trip_details_by_driver_id($driver_id,$type);
					foreach ($all_trips_details as $key => $value) {

						if($value['eTripLocation']=='OutStationByDuration'){
							$all_trips_details[$key]['fMinimumKm']=$this->webservices_model->getOutstationBydurationMinkmMiles($value['iVehicleCompanyId'],$value['iCityId']);
						}else{
							$all_trips_details[$key]['fMinimumKm']='';
						}
						if($value['ePaymentType']=='Credit Card'){
							$card_id = $value['iCustomerCreditCardId'];
							$all_card_info = $this->webservices_model->get_card_info($card_id);
							$credit_card_no = $all_card_info['vCreditcardNo'];
							$all_trips_details[$key]['vCreditcardNo'] = "XXXX XXXX XXXX ".substr($credit_card_no,-4,4); 
						}
						$all_trips_details[$key]['vClientFullName'] = $value['vFirstName'].' '.$value['vLastName'];
						$all_trips_details[$key]['fDistance'] = $value['fDistance']." ".$value['eDistanceUnit'];
						unset($all_trips_details[$key]['eDistanceUnit']);
						$finalepayment = number_format($value['fFinalPayment'],2);
						$all_trips_details[$key]['fFinalPayment'] = $value['vCurrencySymbol'].$finalepayment;
						$value['dTripDate'] = date_create($value['dTripDate']);
						$all_trips_details[$key]['dTripDate'] = date_format($value['dTripDate'], 'jS F Y g:i A');
						$dToDate = date_create($value['dToDate']);
						$all_trips_details[$key]['dToDate']=($value['dToDate']!= '0000-00-00 00:00:00') ? date_format($dToDate, 'jS F Y g:i A') : '' ;
						$all_trips_details[$key]['vRoundOption'] = $value['vRoundOption'];
						$startpointlatlonarr = explode('|', $value['tPickUpAddressLatLong']);
						$all_trips_details[$key]['vPickupLocation_Latitude'] = $startpointlatlonarr[0];
						$all_trips_details[$key]['vPickupLocation_Longitude'] = $startpointlatlonarr[1];
						unset($all_trips_details[$key]['tPickUpAddressLatLong']);
						if ($value['tDestinationAddressLatLong']!='') {
							$finishpointlatlonarr = explode('|', $value['tDestinationAddressLatLong']);
							$all_trips_details[$key]['vDestinationLocation_Latitude'] = $finishpointlatlonarr[0];
							$all_trips_details[$key]['vDestinationLocation_Longitude'] = $finishpointlatlonarr[1];
						} else {
							$all_trips_details[$key]['vDestinationLocation_Latitude'] = '';
							$all_trips_details[$key]['vDestinationLocation_Longitude'] = '';
						}
						
						unset($all_trips_details[$key]['tDestinationAddressLatLong']);

						$all_trips_details[$key]['Customer_Current_Latitude'] = $value['dLatitude'];
						$all_trips_details[$key]['Customer_Current_Longitude'] = $value['dLongitude'];
						unset($all_trips_details[$key]['dLatitude']);
						unset($all_trips_details[$key]['dLongitude']);

						if($value['eStatus']=='Complete'){
							$all_trips_details[$key]['eStatus'] = 'Completed';
						}
						else if($value['eStatus']=='Cancel'){
							$all_trips_details[$key]['eStatus'] = 'Cancelled';
						}
						$all_trips_details[$key]['rideFeedback'] = $this->webservices_model->getRideFeedback($all_trips_details[$key]['iTripId']);
						$all_trips_details[$key]['rideFeedbackArray'] = array();
						if (!empty($all_trips_details[$key]['rideFeedback'])) {
							$all_trips_details[$key]['rideFeedbackArray'][] = $all_trips_details[$key]['rideFeedback'];
						}
					}
				}

				if(count($all_trips_details)>0) {
					$data['data'] = $all_trips_details;
					if($type=='all'){
						$data['type'] = 'all';
					}else {
						$data['type'] = $type;
					}
					$data['msg'] = 'Success';
				}else {
					$data['msg'] = 'No Trip Available';
				}

				$isvehicle=$this->webservices_model->checkAnyVehicleAssignedToDriver($driver_id);
				if($isvehicle>0){
					$data['vehicle_assign']='Yes,vehicle is assigned';
				}else{
					$data['vehicle_assign']='No,vehicle is Not assigned';
				}
				$data['eAvailability'] = $driverInfo['eAvailability'];
				$licenceInfo=$this->webservices_model->GetLicenceInfo($driver_id);
				if($licenceInfo){
					$data['licence_status'] =$licenceInfo['eVerifiedStatus'];
				}else{
					$data['licence_status'] ='Licence Not Found';
				}
			}else {
				$data['msg'] = 'Driver Not Exist';
			}
		}else {
			$data['msg'] = 'Failure';
		}

		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
		$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}

	function OwnerTripList(){
		//  type( all / Pending / Complete)
		$iVehicleOwnerId = $this->input->post('iVehicleOwnerId');
		$type = $this->input->post('type');
		if($iVehicleOwnerId != '' && ($type=='all' || $type=='Pending' || $type=='Complete') ){
			$owner_exists = $this->webservices_model->check_owner_exists($iVehicleOwnerId);
			if ($owner_exists=='exist') {
				// if all/pending put trips which is not confirmed, but notification is send to VO
				$resArr=array();
				if($type=='all' || $type=='Pending'){
					$futureTripArr = $this->webservices_model->allFutureTripsByOwnerID($iVehicleOwnerId);
					foreach ($futureTripArr as $ftkey => $futureTrip) {

						if($futureTrip['eTripLocation']=='OutStationByDuration'){
							$futureTrip['fMinimumKm']=$this->webservices_model->getOutstationBydurationMinkmMiles($futureTrip['iVehicleCompanyId'],$futureTrip['iCityId']);
						}else{
							$futureTrip['fMinimumKm']='';
						}
						$futureTrip['showButton']='AcceptReject';
						$futureTrip['driverFirstName']='';
						$futureTrip['driverLastName']='';
						$futureTrip['rideFeedback'] = $this->webservices_model->getRideFeedback($futureTrip['iTripId']);
						$futureTrip['rideFeedbackArray'] = array();
						if (!empty($futureTrip['rideFeedback'])) {
							$futureTrip['rideFeedbackArray'][] = $futureTrip['rideFeedback'];
						}
						$resArr[]=$futureTrip;
					}
				}
				$tripArr = $this->webservices_model->allTripsDetailsByOwnerID($iVehicleOwnerId,$type);
				if (count($tripArr)>0) {
					foreach ($tripArr as $tkey => $trip) {
						if($trip['eTripLocation']=='OutStationByDuration'){
							$trip['fMinimumKm']=$this->webservices_model->getOutstationBydurationMinkmMiles($trip['iVehicleCompanyId'],$trip['iCityId']);
						}else{
							$trip['fMinimumKm']='';
						}
						if ($trip['eStatus']=='Cancel') {
							$trip['showButton']='NoButton';
							$trip['driverFirstName']='';
							$trip['driverLastName']='';
						}else if ($trip['eStatus']=='Complete') {
							$trip['showButton']='NoButton';
							$trip['driverFirstName']='';
							$trip['driverLastName']='';
						}else if ($trip['iDriverId']==0) {
							$trip['showButton']='AllocateDriver';
							$trip['driverFirstName']='';
							$trip['driverLastName']='';
						} else {
							$driverDetail = $this->webservices_model->get_driver_details($trip['iDriverId']);
							$trip['driverFirstName']=$driverDetail['vFirstName'];
							$trip['driverLastName']=$driverDetail['vLastName'];
							if ($trip['ePickUpRiderNow']=='No' && $trip['eBookType']=='Later') {
								$trip['showButton']='UnAllocateDriver';
							} else {
								$trip['showButton']='NoButton';
							}
						}
						
						$trip['rideFeedback'] = $this->webservices_model->getRideFeedback($trip['iTripId']);
						$trip['rideFeedbackArray'] = array();
						if (!empty($trip['rideFeedback'])) {
							$trip['rideFeedbackArray'][] = $trip['rideFeedback'];
						}
						$resArr[]=$trip;
					}
				}
				if (count($resArr)>0){
					$data['data'] = $resArr;
					$data['msg'] = "Success";
				}else{
					$data['msg'] = "No Record Found";
				}
				// Here
				$data['approved_vehicle_count'] = $this->webservices_model->getCountOfApprovedVehicle($iVehicleOwnerId);
				$data['approved_driver_count'] = $this->webservices_model->getCountOfApprovedDriver($iVehicleOwnerId);
			} else {
				$data['msg'] = "Vehical Owner Not Exist";
			}
		}else{
			$data['msg'] = "Error";
		}
		header('Content-type: application/json');
		$callback = '';
		if (isset($_REQUEST['callback'])){
			$callback = filter_var($_REQUEST['callback'], FILTER_SANITIZE_STRING);
		}
		$main = json_encode($data);
		echo $callback . ''.$main.'';
		exit;
	}
	// ************************* Changes for Phase 3 Ends **************************

	// ************************* Test for Phase 3 Ends **************************
	function testFun(){
		$smsText = 'Tset Message From OneTouchCab ';
		require_once APPPATH."third_party/twilio-php-master/Services/Twilio.php";
		$AccountSid = "AC9bdb8637d16c5ae98193fcacf6060aa9";
		$AuthToken = "2ed7968ba5927bff3b93f76c9c1372d6";
		$client = new Services_Twilio($AccountSid, $AuthToken);
		$people = array( "+919722159392" => "" );
		foreach ($people as $number => $name) { 
			try {
				$sms[] = $client->account->messages->sendMessage("+18562882821",$number,$smsText);
				$res['msg'] = "Success";

			} catch (Exception $e) {
			    echo 'Caught exception: ',  $e->getMessage(), "\n";
				$res['msg'] = "Failure";
			}
		}
		$this->printthisexit($res);
	}

	function sendmailtest(){
		$image = $siteurl.'assets/admin/image/onetouchtruck-logo.png';
		$bodyArr = array("#NAME#", "#PASSWORD#", "#EMAIL#", "#SITEURL#", "#MAILFOOTER#", "#SITE_NAME#", "#LINK#", "#FIRSTNAME#", "#LASTNAME#", "#IMAGE_URL#");
		$postArr = array($name, $password, 'bhavin.shah@techiestown.com', $siteurl, $MailFooter, $siteName, $link, $FirstName, $LastName, $image);
		$sendDriver = $this->Send("CREATE_DRIVER_BY_OWNER", "Driver", 'bhavin.shah@techiestown.com', $bodyArr, $postArr);
		echo "Done";exit;
	}
	// ************************* Test for Phase 3 Ends **************************
	// printthis
	// printthisexit
}
/* End of file webservices.php */
/* Location: ./application/controllers/webservices.php */
?>