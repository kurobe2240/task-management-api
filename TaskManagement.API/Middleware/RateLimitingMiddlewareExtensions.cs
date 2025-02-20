using System.Threading.RateLimiting;
using Microsoft.AspNetCore.RateLimiting;

namespace TaskManagement.API.Middleware;

public static class RateLimitingMiddlewareExtensions
{
    public static IServiceCollection AddRateLimiting(this IServiceCollection services, IConfiguration configuration)
    {
        services.AddRateLimiter(options =>
        {
            // グローバルなレート制限の設定
            options.GlobalLimiter = PartitionedRateLimiter.Create<HttpContext, string>(context =>
            {
                return RateLimitPartition.GetFixedWindowLimiter(
                    partitionKey: context.User.Identity?.Name ?? context.Request.Headers.Host.ToString(),
                    factory: partition => new FixedWindowRateLimiterOptions
                    {
                        AutoReplenishment = true,
                        PermitLimit = 100, // 1ウィンドウあたりの最大リクエスト数
                        Window = TimeSpan.FromMinutes(1), // ウィンドウの時間
                        QueueLimit = 2 // キューの最大サイズ
                    });
            });

            // 拒否された場合のレスポンス設定
            options.RejectionStatusCode = StatusCodes.Status429TooManyRequests;
            options.OnRejected = async (context, token) =>
            {
                context.HttpContext.Response.StatusCode = StatusCodes.Status429TooManyRequests;
                context.HttpContext.Response.ContentType = "application/json";

                var response = new
                {
                    Status = 429,
                    Message = "リクエストの制限を超えました。しばらく待ってから再試行してください。",
                    RetryAfter = 60 // 固定値を設定
                };

                await context.HttpContext.Response.WriteAsJsonAsync(response, token);
            };
        });

        return services;
    }

    public static IApplicationBuilder UseRateLimiting(this IApplicationBuilder app)
    {
        return app.UseRateLimiter();
    }
} 