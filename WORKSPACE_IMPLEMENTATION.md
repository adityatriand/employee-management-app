# Multi-Tenant Workspace Implementation Plan

## Overview
Transform the application into a multi-tenant workspace system where:
- Each user registers and creates their own workspace
- All routes are prefixed with workspace slug: `/{workspace}/...`
- Users can only access their own workspace
- Regular users (level 0) have limited access (view own profile, edit own profile only)

## Implementation Steps

### Phase 1: Database & Models ✅
- [x] Create Workspace model and migration
- [x] Add workspace_id to users table
- [x] Add workspace_id to employees table
- [x] Update User model with workspace relationship
- [x] Update Employee model with workspace relationship

### Phase 2: Core Controllers & Middleware
- [x] Create WorkspaceController
- [x] Create WorkspaceMiddleware
- [ ] Update RegisterController to redirect to workspace setup
- [ ] Update LoginController to handle workspace context
- [ ] Register WorkspaceMiddleware in Kernel

### Phase 3: Routes Restructuring
- [ ] Create workspace setup routes (no middleware)
- [ ] Restructure all routes with workspace prefix
- [ ] Update route names to include workspace parameter
- [ ] Add workspace logo streaming route

### Phase 4: Views
- [ ] Create workspace setup view
- [ ] Update sidebar to show workspace logo and name
- [ ] Create limited dashboard for regular users
- [ ] Update all views to use workspace routes

### Phase 5: Controllers Updates
- [ ] Update all controllers to scope by workspace
- [ ] Update EmployeeController to create user account for regular users
- [ ] Update queries to filter by workspace_id

### Phase 6: Authentication
- [ ] Update login to check workspace
- [ ] Update registration flow
- [ ] Handle workspace context in sessions

## Route Structure

### Public Routes (No Workspace)
```
GET  /                          -> Landing page
GET  /register                  -> User registration
POST /register                  -> Create user account
GET  /login                     -> Login page (select workspace)
POST /login                     -> Authenticate
```

### Workspace Routes (With {workspace} prefix)
```
GET  /{workspace}/setup         -> Workspace setup (after registration)
POST /{workspace}/setup         -> Create workspace
GET  /{workspace}/login         -> Workspace login
POST /{workspace}/login         -> Authenticate for workspace
GET  /{workspace}/dashboard     -> Dashboard (admin or regular)
GET  /{workspace}/employees     -> Employee list
GET  /{workspace}/employees/{id} -> Employee detail
...
```

## User Flow

1. **Registration:**
   - User registers at `/register`
   - Redirected to `/{workspace}/setup` (no workspace yet)
   - User enters workspace name and uploads logo
   - Workspace created, user becomes admin (level 1)
   - Redirected to `/{workspace}/dashboard`

2. **Login:**
   - User goes to `/{workspace}/login`
   - Enters email/password
   - System checks if user belongs to workspace
   - If yes, login and redirect to `/{workspace}/dashboard`
   - If no, show error

3. **Regular User Creation:**
   - Admin creates employee
   - System creates user account (level 0) for employee
   - Employee receives login link: `/{workspace}/login`
   - Regular user can only:
     - View own employee detail page
     - Edit own profile
     - Everything else is view-only

## Security Considerations

- WorkspaceMiddleware checks user belongs to workspace
- All queries must filter by workspace_id
- Regular users cannot access admin routes
- Workspace isolation enforced at database level

