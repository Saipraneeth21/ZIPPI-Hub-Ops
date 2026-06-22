<?php

namespace App\Enums;

/**
 * Centralized string-backed enums for rental domain states.
 * Kept in one file for MVP brevity; mirrors the state machines in
 * Database/03-Data-Relationships.md.
 */

enum KycStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}

enum PaymentStatus: string
{
    case Created = 'created';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Failed = 'failed';
    case Refunded = 'refunded';
}

enum RefundStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
}

enum DurationType: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
}

enum BikeStatus: string
{
    case Available = 'available';
    case Booked = 'booked';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';
}

enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case Ops = 'ops';
    case KycReviewer = 'kyc_reviewer';
    case Support = 'support';

    // Full-access roles (see AdminAccess::FULL_ACCESS_ROLES).
    case Admin = 'admin';
    case Developer = 'developer';
    case Manager = 'manager';

    // Limited roles: dashboard view, users view, instant-dispatch accept only.
    case Supervisor = 'supervisor';
    case Telecaller = 'telecaller';
    case Marketer = 'marketer';

    /** Human-friendly label for UI (forms, tables). */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Ops => 'Ops',
            self::KycReviewer => 'KYC Reviewer',
            self::Support => 'Support',
            self::Admin => 'Admin',
            self::Developer => 'Developer',
            self::Manager => 'Manager',
            self::Supervisor => 'Supervisor',
            self::Telecaller => 'Telecaller',
            self::Marketer => 'Marketer',
        };
    }
}
