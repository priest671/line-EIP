<?php

namespace App\Http\Controllers\View;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use DB;
class leavetypelist extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $users = DB::select('select * from user', []);
        // return response()->json([
        //     'status' => 'successful',
        //     'data' => $users
        // ]);
    }

    /**
     * 顯示假別資料頁面
     * @author nino
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $page = Input::get('page', 1);
        $types = DB::select('select elt.*, et.name as title_name, et.id as title_id from eip_leave_type elt, eip_title et where elt.approved_title_id = et.id order by name limit ?,10 ', [($page-1)*10]);
        $total_types = DB::select('select * from eip_leave_type', []);
        $total_pages = ceil(count($total_types)/10);
        return view('contents.leavetypelist', [
            'types' => $types, 
            'page' => $page,
            'total_pages' => $total_pages
        ]);
    }

    /**
     * 新增假別資料
     * @author nino
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //debug( $request->get('name'));
        $name = $request->get('name');
        $day = $request->get('day');
        $approved_title_id = $request->get('approved_title_id');
        //檢查參數格式是否正確
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:32',
            'day' => 'required|integer',
            'approved_title_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->all()
            ], 400);
        }
        //檢查一樣天數的假是否存在
        $types = DB::select('select name from eip_leave_type where name =? and day =?', [$name, $day]);
        if(count($types) > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'name exists'
            ], 409);
        }

        if(DB::insert("insert into eip_leave_type (name, day, approved_title_id) values (?, ?, ?)", [$name, $day, $approved_title_id]) == 1) {
            return response()->json([
                'status' => 'successful'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'insert error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 修改假別資料
     * @author nino
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $name = $request->get('name');
        $day = $request->get('day');
        $approved_title_id = $request->get('approved_title_id');
        //檢查參數格式是否正確
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:32',
            'day' => 'required|integer',
            'approved_title_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->all()
            ], 400);
        }
        //檢查一樣天數的假是否存在
        $types = DB::select('select name from eip_leave_type where name =? and day =? and id != ?', [$name, $day, $id]);
        if(count($types) > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'name exists'
            ], 409);
        }

        if(DB::update("update eip_leave_type set name =?, day =?, approved_title_id =? where id =?", [$name, $day, $approved_title_id, $id]) == 1) {
            return response()->json([
                'status' => 'successful'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'update error'
            ], 500);
        }
    }

    /**
     * 刪除假別資料
     * @author nino
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(DB::delete("delete from eip_leave_type where id =?", [$id]) == 1) {
            return response()->json([
                'status' => 'successful'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'delete error'
            ], 500);
        }
    }
}
