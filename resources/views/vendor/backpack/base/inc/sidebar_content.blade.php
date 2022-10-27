{{-- This file is used to store sidebar items, inside the Backpack admin panel --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('user') }}"><i class="nav-icon la la-question"></i> Users</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('rum') }}"><i class="nav-icon la la-question"></i> Rums</a></li>

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('rum-post') }}"><i class="nav-icon la la-question"></i> Rum posts</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('comment') }}"><i class="nav-icon la la-question"></i> Comments</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('comment-reply') }}"><i class="nav-icon la la-question"></i> Comment replies</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('subscription') }}"><i class="nav-icon la la-question"></i> Subscriptions</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('history-payment') }}"><i class="nav-icon la la-question"></i> History payments</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('suportemail') }}"><i class="nav-icon la la-question"></i> Suportemails</a></li>