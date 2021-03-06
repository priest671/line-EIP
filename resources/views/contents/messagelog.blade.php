@extends('layouts.master')
@section('title', 'Home')
@section('content')
<div class="container-fluid pt-lg-4">
  <form id="search_form" method="POST" action="{{ route('messagelog') }}">
    {{ csrf_field() }}
    <div class="row">
      <div class="col-sm-4 form-row">
        <div class="col-auto">
          <input type="text" name="search" class="form-control" placeholder="帳號或lineId" value="{{ $search }}">
        </div>
        <div class="col-auto">
          <button type="button" class="btn-c"  onclick="reload_page(1, '{{$order_col}}', '{{$order_type}}', 'search')">搜尋</button>
        </div>
      </div>
    </div>
  </form>
  <div class="row p-lg-3">
    <table class="table table-bordered table-striped">
      <thead class="table-thead">
        <tr>
          <th scope="col" onclick="reload_page({{$page}}, 'username', '{{$order_type}}', 'col')">帳號
            @if ($order_col == 'username' && $order_type == 'DESC') <div class="angle-down"></div> @endif
            @if ($order_col == 'username' && $order_type == 'ASC') <div class="angle-up"></div> @endif
          </th>
          <!-- <th scope="col" onclick="reload_page({{$page}}, 'email', '{{$order_type}}', 'col')">LineChannel
            @if ($order_col == 'email' && $order_type == 'DESC') <div class="angle-down"></div> @endif
            @if ($order_col == 'email' && $order_type == 'ASC') <div class="angle-up"></div> @endif
          </th> -->
          <th scope="col" onclick="reload_page({{$page}}, 'line_id', '{{$order_type}}', 'col')">LineId
            @if ($order_col == 'line_id' && $order_type == 'DESC') <div class="angle-down"></div> @endif
            @if ($order_col == 'line_id' && $order_type == 'ASC') <div class="angle-up"></div> @endif
          </th>
          <th scope="col" onclick="reload_page({{$page}}, 'type', '{{$order_type}}', 'col')">type
            @if ($order_col == 'type' && $order_type == 'DESC') <div class="angle-down"></div> @endif
            @if ($order_col == 'type' && $order_type == 'ASC') <div class="angle-up"></div> @endif
          </th>
          <th scope="col" onclick="reload_page({{$page}}, 'message', '{{$order_type}}', 'col')">message
            @if ($order_col == 'message' && $order_type == 'DESC') <div class="angle-down"></div> @endif
            @if ($order_col == 'message' && $order_type == 'ASC') <div class="angle-up"></div> @endif
          </th>
          <th scope="col" onclick="reload_page({{$page}}, 'time', '{{$order_type}}', 'col')">時間
            @if ($order_col == 'time' && $order_type == 'DESC') <div class="angle-down"></div> @endif
            @if ($order_col == 'time' && $order_type == 'ASC') <div class="angle-up"></div> @endif
          </th>
        </tr>
      </thead>
      <tbody>
        @if(count($messages) === 0) 
          <tr>
            <td colspan="5" class="text-center"> 目前無資料 </td>
          </tr>
        @else
          @foreach($messages as $msg)
            <tr>
              <td> {{$msg->username}} </td>
              <!-- <td> {{$msg->line_channel}} </td> -->
              <td> {{$msg->line_id}} </td>
              <td> {{$msg->type}} </td>
              <td> {{$msg->message}} </td>  
              <td> {{date('Y-m-d H:i:s', $msg->time)}} </td>  
            </tr>
          @endforeach
        @endif
      </tbody>
    </table>
  </div>
  <div class="row">
    <div class="col-md-6 offset-md-3">
      <nav aria-label="Page navigation example">
      <ul class="pagination justify-content-center">
        <li class="page-item @if ($page == 1) disabled @endif " onclick="reload_page({{$page-1}}, '{{$order_col}}', '{{$order_type}}', 'page')">
          <a class="page-link">上一頁</a>
        </li>
        @for ($i = 1; $i <= $total_pages; $i++)
          <li class="page-item @if ($i == $page) active @endif" onclick="reload_page({{$i}}, '{{$order_col}}', '{{$order_type}}', 'page')">
            <a class="page-link">{{$i}}</a>
          </li>
        @endfor
        <li class="page-item @if ($page == $total_pages) disabled @endif" onclick="reload_page({{$page+1}}, '{{$order_col}}', '{{$order_type}}', 'page')">
          <a class="page-link">下一頁</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
</div>
<!-- Modal -->
<div class="modal fade" id="setModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">設定職等和第一簽核人</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-group container-fluid">
            <div class="row">
              <label for="title-name" class="col-form-label w-25">職等:</label>
              <div class="col-form-label w-75">
                <select id="title_set_select"></select>
              </div>
            </div>
            <div class="row">
              <label for="title-name" class="col-form-label w-25">第一簽核人:</label>
              <div class="col-form-label w-75">
                <select id="upper_user_set_select" class="w-75"></select>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
        <button type="button" class="btn btn-primary todo">新增</button>
      </div>
    </div>
  </div>
</div>
<script src="{{ asset('js/views/messagelog.js') }}"></script>
@endsection
