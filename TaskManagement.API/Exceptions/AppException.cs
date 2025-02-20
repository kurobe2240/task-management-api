using System;
using System.Net;

namespace TaskManagement.API.Exceptions
{
    public class AppException : Exception
    {
        public HttpStatusCode StatusCode { get; }

        public AppException(string message, HttpStatusCode statusCode = HttpStatusCode.BadRequest) 
            : base(message)
        {
            StatusCode = statusCode;
        }

        public AppException(string message, Exception innerException, HttpStatusCode statusCode = HttpStatusCode.BadRequest) 
            : base(message, innerException)
        {
            StatusCode = statusCode;
        }
    }

    public class NotFoundException : AppException
    {
        public NotFoundException(string message) 
            : base(message, HttpStatusCode.NotFound)
        {
        }
    }

    public class UnauthorizedException : AppException
    {
        public UnauthorizedException(string message = "認証に失敗しました。") 
            : base(message, HttpStatusCode.Unauthorized)
        {
        }
    }

    public class ForbiddenException : AppException
    {
        public ForbiddenException(string message = "このリソースへのアクセス権限がありません。") 
            : base(message, HttpStatusCode.Forbidden)
        {
        }
    }

    public class ConflictException : AppException
    {
        public ConflictException(string message) 
            : base(message, HttpStatusCode.Conflict)
        {
        }
    }
} 