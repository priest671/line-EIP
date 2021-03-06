<?php

namespace App\Http\Controllers\View\LinePersonalOperate;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Repositories\LeaveApplyRepository;
use App\Services\UserService;
use App\Services\SendLineMessageService;
use App\Services\ApplyLeaveService;
use DB;
use Log;
use Exception;

class applyoverwork extends Controller
{
    protected $userService;
    protected $applyLeaveService;
    protected $leaveApplyRepo;
    protected $sendLineMessageService;

    public function __construct(
        UserService $userService,
        ApplyLeaveService $applyLeaveService,
        LeaveApplyRepository $leaveApplyRepo,
        SendLineMessageService $sendLineMessageService
    )
    {
        $this->userService = $userService;
        $this->applyLeaveService = $applyLeaveService;
        $this->leaveApplyRepo = $leaveApplyRepo;
        $this->sendLineMessageService = $sendLineMessageService;
    }

    /**
     * 顯示applyoverwork頁面
     */
    public function create()
    {
        return view('contents.LinePersonalOperate.applyoverwork', [
            'nowdate' => date("Y-m-d")
        ]);
    }

    /**
     * 新增一筆加班紀錄
     * 3個step:
     * step1: 取得要寫入請假相關table的資料(假別id,申請人、代理人和第一簽核人資料)
     * step2: 寫入加班和簽核流程table
     * step3: 通知申請人和第一簽核人
     * 
     * @param \Illuminate\Http\Request
     */
    public function store(Request $request)
    {
        try {
            $apply_user_id  = $request->get('userId');          //申請者的line_id
            $overworkDate   = $request->get('overworkDate');    //加班日
            $overworkHour   = $request->get('overworkHour');    //加班小時
            $comment        = $request->get('comment');         //備註
            $use_mode       = $request->get('use_mode');    
            if($comment == "") $comment = "-";
            
            $start_m = date_format(date_create($overworkDate),"Ym");
            $now_m = date("Ym");
            if($start_m < $now_m) throw new Exception('只能申請這個月以後的加班'); 

            //取得申請人的基本資料
            $user = $this->userService->get_user_info($apply_user_id, $use_mode);
            if($user['status'] == 'error') throw new Exception($user['message']);
            $apply_user_no = $user['data']->NO;
            $apply_user_cname = $user['data']->cname;
            $apply_user_line_id = $user['data']->line_id; 

            if($this->leaveApplyRepo->check_overwork_is_overlap($apply_user_no, $overworkDate)) {
                throw new Exception('加班日內已有其它加班'); 
            }

            //透過加班小時找到加班type_id
            $overwork_type_arr = DB::select('select * from eip_overwork_type', []);
            $overwork_type_id = "";
            $overwork_approved_title_id = "";
            foreach ($overwork_type_arr as $v) {
                if($overworkHour < $v->hour) {
                    $overwork_type_id = $v->id;
                    $overwork_approved_title_id = $v->approved_title_id;
                    break;
                }
            }
            
            //取得第一簽核人的資料
            $upper_users = $this->_get_upper_users_info($apply_user_id,$use_mode);
            $upper_line_id = "";    //第一簽核人的line_id
            $upper_user_no = "";    //第一簽核人的user_no
            foreach ($upper_users as $v) {
                $upper_line_id = $v->line_id; 
                $upper_user_no = $v->NO; 
            }
            if($upper_line_id == "" || $upper_user_no == "") throw new Exception('請加班:未設定簽核人或簽核人的line未加入EIP中');

            //寫入請假紀錄
            $sql = "insert into eip_leave_apply ";
            $sql .= "(apply_user_no, apply_type, leave_type, over_work_date, over_work_hours, comment) ";
            $sql .= "value ";
            $sql .= "(?, ?, ?, ?, ?, ?) ";
            if(DB::insert($sql, [$apply_user_no, 'O', $overwork_type_id, $overworkDate, $overworkHour, $comment]) != 1) {
                throw new Exception('insert eip_leave_apply error'); 
            }
            //取得剛剛寫入的請假紀錄id
            $last_appy_record = DB::select('select max(id) as last_id from eip_leave_apply');
            $last_appy_id = ""; //假單流水號
            foreach ($last_appy_record as $v) {
                $last_appy_id = $v->last_id;
            }
            
            //寫入簽核流程紀錄(該table沒有紀錄申請人和簽核人的line_id是因為可能會有換line帳號的情況發生)
            $upper_users = $this->applyLeaveService->find_upper($apply_user_no, $apply_user_no, [], $overwork_approved_title_id);
            foreach ($upper_users as $u) {
                if(DB::insert("insert into eip_leave_apply_process (apply_id, apply_type, apply_user_no, upper_user_no) value (?, ?, ?, ?)", [$last_appy_id, 'O', $apply_user_no, $u]) != 1) {
                    DB::delete("delete from eip_leave_apply where id = ?", [$last_appy_id]);
                    DB::delete("delete from eip_leave_apply_process where apply_id = ?", [$last_appy_id]);
                    throw new Exception('insert db error'); 
                }
            }

            //通知申請人、代理人、第一簽核人
            //發出line通知
            $this->sendLineMessageService->sendNotify($last_appy_id, 'apply_overwork');

            return response()->json([
                'status' => 'successful'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 取得第一簽核人的資料
     *
     * @param  string   $apply_user_id
     * @param  string   $use_mode
     * @return array    
     */
    private function _get_upper_users_info($apply_user_id,$use_mode){

        if($use_mode == 'web'){
            return  DB::select('select NO, line_id from user where NO in (select upper_user_no from user where NO =?)', [$apply_user_id]);
        }
        return DB::select('select NO, line_id from user where NO in (select upper_user_no from user where line_id =?)', [$apply_user_id]);
    }

}
