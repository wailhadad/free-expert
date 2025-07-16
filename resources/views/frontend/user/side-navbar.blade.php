<div class="col-lg-3">
  <div class="user-sidebar radius-md mb-40">
    <ul class="links list-unstyled">
      <li>
        <a href="{{ route('user.dashboard') }}" class="{{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
          {{ __('Dashboard') }}
        </a>
      </li>
      <li>
        <a href="{{ route('user.notifications') }}" class="{{ request()->routeIs('user.notifications*') ? 'active' : '' }}">
          {{ __('Notifications') }}
        </a>
      </li>
      <li>
        <a href="{{ route('user.discussions') }}" class="{{ request()->routeIs('user.discussions') ? 'active' : '' }}">
          <i class="fas fa-comments"></i> {{ __('Messages') }}
        </a>
      </li>
      @if ($basicInfo->is_service)
        <li>
          <a href="{{ route('user.service_orders') }}"
            class="{{ request()->routeIs('user.service_orders') || request()->routeIs('user.service_order.details') ? 'active' : '' }}">
            {{ __('Service Orders') }}
          </a>
        </li>

        <li>
          <a href="{{ route('user.service_wishlist') }}"
            class="{{ request()->routeIs('user.service_wishlist') ? 'active' : '' }}">
            {{ __('Service Wishlist') }}
          </a>
        </li>
        <li>
          <a href="{{ route('user.customer-briefs.index') }}" class="{{ request()->routeIs('user.customer-briefs.*') ? 'active' : '' }}">
            <i class="fas fa-briefcase"></i> {{ __('Briefs') }}
          </a>
        </li>
      @endif
      @if (!$basicInfo->is_service)
        <li>
          <a href="{{ route('user.customer-briefs.index') }}" class="{{ request()->routeIs('user.customer-briefs.*') ? 'active' : '' }}">
            <i class="fas fa-briefcase"></i> {{ __('Briefs') }}
          </a>
        </li>
      @endif
      @if ($basicInfo->support_ticket_status == 1)
        <li>
          <a href="{{ route('user.support_tickets') }}"
            class="@if (request()->routeIs('user.support_tickets')) active
            @elseif (request()->routeIs('user.support_tickets.create')) active
            @elseif (request()->routeIs('user.support_ticket.conversation')) active @endif">
            {{ __('Support Tickets') }}
          </a>
        </li>
      @endif
      <li>
        <a href="{{ route('user.packages.subscription_log') }}" class="{{ request()->routeIs('user.packages.subscription_log') ? 'active' : '' }}">
          <span>{{ __('Subscription Log') }}</span>
        </a>
      </li>

      <!-- Agency Privileges Section -->

      @if(Auth::guard('web')->user() && Auth::guard('web')->user()->hasAgencyPrivileges())
        <li>
          <a href="{{ route('user.subusers.index') }}"
            class="{{ request()->routeIs('user.subusers.*') ? 'active' : '' }}">
            <i class="fas fa-users"></i> {{ __('Subusers') }}
            <span class="badge badge-light ml-2">{{ Auth::guard('web')->user()->current_subusers_count }}/{{ Auth::guard('web')->user()->max_subusers }}</span>
          </a>
        </li>
      @endif

      <li>
        <a href="{{ route('user.followings') }}" class="{{ request()->routeIs('user.followings') ? 'active' : '' }}">
          {{ __('Following') }}
        </a>
      </li>


      <li>
        <a href="{{ route('user.edit_profile') }}"
          class="{{ request()->routeIs('user.edit_profile') ? 'active' : '' }}">
          {{ __('Edit Profile') }}
        </a>
      </li>

      @php $authUser = Auth::guard('web')->user(); @endphp

      @if (!is_null($authUser->password))
        <li>
          <a href="{{ route('user.change_password') }}"
            class="{{ request()->routeIs('user.change_password') ? 'active' : '' }}">
            {{ __('Change Password') }}
          </a>
        </li>
      @endif
      <li>
        <a href="{{ route('user.logout') }}">
          {{ __('Logout') }}
        </a>
      </li>
    </ul>
  </div>
</div>
