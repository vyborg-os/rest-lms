# Library Management System API Documentation

## Base URL
All API endpoints are relative to the base URL: `/api/`

## Authentication
Most endpoints require authentication using JWT tokens. Include the token in the Authorization header:

```
Authorization: Bearer <your_jwt_token>
```

## Endpoints

### Authentication

#### Login
- **URL**: `/users/login`
- **Method**: `POST`
- **Auth Required**: No
- **Request Body**:
  ```json
  {
    "username": "admin",
    "password": "password"
  }
  ```
- **Success Response**: 
  ```json
  {
    "token": "jwt_token_here",
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
  ```

#### Register
- **URL**: `/users/register`
- **Method**: `POST`
- **Auth Required**: No (Admin auth required to create admin users)
- **Request Body**:
  ```json
  {
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "password",
    "role": "patron"
  }
  ```
- **Success Response**: 
  ```json
  {
    "message": "User registered successfully",
    "token": "jwt_token_here",
    "user": {
      "id": 3,
      "username": "newuser",
      "email": "newuser@example.com",
      "role": "patron"
    }
  }
  ```

### Books

#### Get All Books
- **URL**: `/books`
- **Method**: `GET`
- **Auth Required**: No
- **Success Response**: Array of book objects

#### Get Book by ID
- **URL**: `/books/{id}`
- **Method**: `GET`
- **Auth Required**: No
- **Success Response**: Book object

#### Add Book
- **URL**: `/books`
- **Method**: `POST`
- **Auth Required**: Yes (Admin only)
- **Request Body**:
  ```json
  {
    "title": "New Book",
    "author": "Author Name",
    "isbn": "1234567890123",
    "total_copies": 5,
    "available_copies": 5,
    "quantity": 5,
    "shelf": "A1",
    "category": "Fiction",
    "description": "Book description",
    "published_year": 2023,
    "publisher": "Publisher Name",
    "cover_image": "https://example.com/cover.jpg"
  }
  ```
- **Success Response**: Created book object

#### Update Book
- **URL**: `/books/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin only)
- **Request Body**: Any book properties to update
- **Success Response**: Updated book object

#### Delete Book
- **URL**: `/books/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes (Admin only)
- **Success Response**: 
  ```json
  {
    "message": "Book deleted successfully"
  }
  ```

### Circulation

#### Reserve Book
- **URL**: `/circulation/reserve`
- **Method**: `POST`
- **Auth Required**: Yes
- **Request Body**:
  ```json
  {
    "book_id": 1,
    "due_date": "2023-08-01 00:00:00"
  }
  ```
- **Success Response**: 
  ```json
  {
    "message": "Book reserved successfully",
    "circulation": {
      "id": 1,
      "user_id": 2,
      "book_id": 1,
      "action": "reserve",
      "action_date": "2023-07-18 10:30:00",
      "due_date": "2023-08-01 00:00:00",
      "fine_amount": "0.00",
      "returned": 0
    }
  }
  ```

#### Borrow Book (Legacy)
- **URL**: `/circulation/borrow`
- **Method**: `POST`
- **Auth Required**: Yes
- **Request Body**: Same as reserve
- **Success Response**: Same as reserve

#### Return Book
- **URL**: `/circulation/return`
- **Method**: `POST`
- **Auth Required**: Yes
- **Request Body**:
  ```json
  {
    "circulation_id": 1
  }
  ```
- **Success Response**: 
  ```json
  {
    "message": "Book returned successfully",
    "circulation": {
      "id": 1,
      "user_id": 2,
      "book_id": 1,
      "action": "return",
      "action_date": "2023-07-18 10:30:00",
      "due_date": "2023-08-01 00:00:00",
      "fine_amount": "0.00",
      "returned": 1
    }
  }
  ```

#### Approve Reservation
- **URL**: `/circulation/approve`
- **Method**: `POST`
- **Auth Required**: Yes (Admin only)
- **Request Body**:
  ```json
  {
    "circulation_id": 1
  }
  ```
- **Success Response**: 
  ```json
  {
    "message": "Reservation approved successfully",
    "circulation": {
      "id": 1,
      "user_id": 2,
      "book_id": 1,
      "action": "borrow",
      "action_date": "2023-07-18 10:30:00",
      "due_date": "2023-08-01 00:00:00",
      "fine_amount": "0.00",
      "returned": 0
    }
  }
  ```

#### Cancel Reservation
- **URL**: `/circulation/cancel`
- **Method**: `POST`
- **Auth Required**: Yes
- **Request Body**:
  ```json
  {
    "circulation_id": 1
  }
  ```
- **Success Response**: 
  ```json
  {
    "message": "Reservation cancelled successfully"
  }
  ```

#### Get Borrowed Books
- **URL**: `/circulation/borrowed`
- **Method**: `GET`
- **Auth Required**: Yes
- **Query Parameters**: `user_id` (optional, admin only)
- **Success Response**: Array of borrowed book objects

### Dashboard

#### Get Dashboard Stats
- **URL**: `/dashboard/stats`
- **Method**: `GET`
- **Auth Required**: Yes
- **Success Response**: 
  ```json
  {
    "totalBooks": 125,
    "availableBooks": 98,
    "borrowedBooks": 27,
    "notifications": [
      {
        "id": 1,
        "title": "Welcome",
        "message": "Welcome to the Library Management System!",
        "date": "2023-07-18 10:30:00"
      }
    ]
  }
  ```

### Notifications

#### Get User Notifications
- **URL**: `/notifications/user/{userId}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Success Response**: Array of notification objects

### Users

#### Get All Users
- **URL**: `/users`
- **Method**: `GET`
- **Auth Required**: Yes (Admin only)
- **Success Response**: Array of user objects

#### Update User
- **URL**: `/users/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin or own account)
- **Request Body**: User properties to update
- **Success Response**: 
  ```json
  {
    "message": "User updated successfully",
    "user": {
      "id": 2,
      "username": "updated_username",
      "email": "updated@example.com",
      "role": "patron"
    }
  }
  ```

#### Delete User
- **URL**: `/users/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes (Admin only)
- **Success Response**: 
  ```json
  {
    "message": "User deleted successfully"
  }
  ```

## Error Responses

All endpoints return appropriate HTTP status codes:

- `200 OK`: Request successful
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication required or invalid token
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `500 Internal Server Error`: Server error

Error response format:
```json
{
  "message": "Error message here"
}
```
