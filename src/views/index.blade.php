@extends('easy-crud::layouts.info')

@section('blockTitle')
    {{ $pageTitle }}
@endsection

@section('dataBlock')
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
            <tr class="text-capitalize">
                <th style="width: 25px">SL</th>
                @foreach($listColumns as $item)
                    <th>{{$item}}</th>
                @endforeach
                @if(@$isCreatable || @$isEditable  || @$isViewable  || @$isDeletable )
                    <th style=" min-width: 80px; ">Action</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($data as $key => $item)
                <tr id="sim-{{ $item->id }}">
                    <td>{{ $key + 1 + (($data->currentPage() <= 1) ? 0 : (($data->currentPage() - 1) * $data->perPage())) }}</td>
                    
                    @foreach($listColumns as $k => $v)
                        @if (in_array($k, ['status', 'is_active']))
                            <td>{{ getStatus(($item->{$k}?:0))}}</td>
                        @elseif (@$formItems[$k][1] == 'image')
                            <td><img style="max-height: 100px" src="{{ getFileUrl($uploadPath, $item->{$k}) }}" /></td>
                        @elseif (is_object($item->{$k}))
                            <td>{{ $item->{$k}->title }}</td>
                        @else
                            @if(@$item->{$k})
                                <td>{{ $item->{$k} }}</td>
                            @else
                                <td>N/A</td>
                            @endif
                        @endif
                    @endforeach
                    
                    @include('easy-crud::partials.list_view_action')
                </tr>
            @empty
                <tr>
                    <td colspan="50" class="text-center"><b>No record found</b></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    
    @if ($data->lastPage() > 1)
        <div class="card-footer clearfix text-right">
            {{ $data->appends(request()->query())->links() }}
        </div>
    @endif
@endsection