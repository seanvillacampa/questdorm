{{--
    The owner dashboard is identical to the staff dashboard.
    Role-specific extras (Reports, Owner Admin nav items) are toggled
    inside the shared view based on auth()->user()->hasRole('owner').
--}}
@include('staff.dashboard')
