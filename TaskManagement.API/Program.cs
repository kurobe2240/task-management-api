using Microsoft.EntityFrameworkCore;
using Microsoft.OpenApi.Models;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.IdentityModel.Tokens;
using System.Text;
using FluentValidation;
using FluentValidation.AspNetCore;
using TaskManagement.API.Data;
using TaskManagement.API.Profiles;
using TaskManagement.API.Models;
using TaskManagement.API.Validators;
using TaskManagement.API.Services;
using TaskManagement.API.Settings;
using TaskManagement.API.Middleware;
using Serilog;
using Serilog.Events;
using Microsoft.AspNetCore.Diagnostics.HealthChecks;
using Microsoft.Extensions.Diagnostics.HealthChecks;

var builder = WebApplication.CreateBuilder(args);

// Kestrelの設定
builder.WebHost.ConfigureKestrel(serverOptions =>
{
    serverOptions.ListenAnyIP(5015); // HTTP
});

// Serilogの設定
Log.Logger = new LoggerConfiguration()
    .ReadFrom.Configuration(builder.Configuration)
    .Enrich.FromLogContext()
    .CreateLogger();

builder.Host.UseSerilog();

// JWT設定
var jwtSettingsSection = builder.Configuration.GetSection("JwtSettings");
builder.Services.Configure<JwtSettings>(jwtSettingsSection);
var jwtSettings = jwtSettingsSection.Get<JwtSettings>();

if (jwtSettings == null)
{
    throw new InvalidOperationException("JwtSettings configuration is missing.");
}

builder.Services.AddSingleton(jwtSettings);

// 認証設定
builder.Services.AddAuthentication(JwtBearerDefaults.AuthenticationScheme)
    .AddJwtBearer(options =>
    {
        options.TokenValidationParameters = new TokenValidationParameters
        {
            ValidateIssuer = true,
            ValidateAudience = true,
            ValidateLifetime = true,
            ValidateIssuerSigningKey = true,
            ValidIssuer = jwtSettings.Issuer,
            ValidAudience = jwtSettings.Audience,
            IssuerSigningKey = new SymmetricSecurityKey(
                Encoding.UTF8.GetBytes(jwtSettings.SecretKey))
        };
    });

// 認可設定
builder.Services.AddAuthorization(options =>
{
    options.AddPolicy("RequireAdminRole", policy => 
        policy.RequireRole("Admin"));
});

// キャッシュの設定
builder.Services.AddMemoryCache();
builder.Services.AddScoped<ICacheService, CacheService>();

// レート制限の設定
builder.Services.AddRateLimiting(builder.Configuration);

// サービスの登録
builder.Services.AddScoped<IAuthService, AuthService>();

// Add services to the container.
builder.Services.AddControllers();
builder.Services.AddFluentValidationAutoValidation()
    .AddValidatorsFromAssemblyContaining<TaskItemValidator>()
    .AddValidatorsFromAssemblyContaining<CreateProjectValidator>()
    .AddValidatorsFromAssemblyContaining<RegisterUserValidator>();

// Database configuration
builder.Services.AddDbContext<ApplicationDbContext>(options =>
{
    options.UseMySql(
        builder.Configuration.GetConnectionString("DefaultConnection"),
        ServerVersion.AutoDetect(builder.Configuration.GetConnectionString("DefaultConnection"))
    );
});

// Repository registration
builder.Services.AddScoped<ITaskRepository, TaskRepository>();
builder.Services.AddScoped<IProjectRepository, ProjectRepository>();

// AutoMapper configuration
builder.Services.AddAutoMapper(typeof(TaskItemProfile), typeof(ProjectProfile), typeof(UserProfile));

// CORS configuration
var corsOrigins = builder.Configuration.GetSection("Cors:AllowedOrigins").Get<string[]>();
if (corsOrigins == null || !corsOrigins.Any())
{
    corsOrigins = new[] { "http://localhost:5173", "http://localhost:5015" };
}

builder.Services.AddCors(options =>
{
    options.AddPolicy("AllowAll", builder =>
    {
        builder.WithOrigins(corsOrigins)
               .AllowAnyMethod()
               .AllowAnyHeader()
               .AllowCredentials();
    });
});

// Swagger configuration
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen(c =>
{
    c.SwaggerDoc("v1", new OpenApiInfo { Title = "TaskManagement.API", Version = "v1" });

    // JWT認証の設定
    c.AddSecurityDefinition("Bearer", new OpenApiSecurityScheme
    {
        Description = "JWT Authorization header using the Bearer scheme.",
        Name = "Authorization",
        In = ParameterLocation.Header,
        Type = SecuritySchemeType.ApiKey,
        Scheme = "Bearer"
    });

    c.AddSecurityRequirement(new OpenApiSecurityRequirement
    {
        {
            new OpenApiSecurityScheme
            {
                Reference = new OpenApiReference
                {
                    Type = ReferenceType.SecurityScheme,
                    Id = "Bearer"
                }
            },
            Array.Empty<string>()
        }
    });
});

// ElasticSearchの設定
// builder.Services.Configure<ElasticSearchSettings>(
//     builder.Configuration.GetSection("ElasticSearch"));
// builder.Services.AddScoped<ISearchService, SearchService>();

// Add health checks
var connectionString = builder.Configuration.GetConnectionString("DefaultConnection");
if (string.IsNullOrEmpty(connectionString))
{
    throw new InvalidOperationException("Database connection string is not configured.");
}

builder.Services.AddHealthChecks()
    .AddMySql(
        connectionString,
        name: "mysql",
        tags: new[] { "db", "mysql", "core" },
        timeout: TimeSpan.FromSeconds(30)
    );
    // .AddElasticsearch(builder.Configuration["ElasticSearch:Url"]);

var app = builder.Build();

// グローバル例外ハンドラーの追加
app.UseMiddleware<ExceptionMiddleware>();

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI(c =>
    {
        c.SwaggerEndpoint("/swagger/v1/swagger.json", "TaskManagement.API v1");
        c.RoutePrefix = "swagger";
    });
}

app.UseDefaultFiles();
app.UseStaticFiles();

app.UseCors("AllowAll");
// app.UseHttpsRedirection();

// レート制限の適用
app.UseRateLimiting();

// 認証・認可の追加
app.UseAuthentication();
app.UseAuthorization();

// Serilogリクエストログの追加
app.UseSerilogRequestLogging(options =>
{
    options.MessageTemplate = "HTTP {RequestMethod} {RequestPath} responded {StatusCode} in {Elapsed:0.0000} ms";
});

// Add health check endpoint
app.MapHealthChecks("/health", new HealthCheckOptions
{
    ResponseWriter = async (context, report) =>
    {
        context.Response.ContentType = "application/json";
        var response = new
        {
            status = report.Status.ToString(),
            checks = report.Entries.Select(x => new
            {
                name = x.Key,
                status = x.Value.Status.ToString(),
                description = x.Value.Description,
                duration = x.Value.Duration.ToString()
            })
        };
        await context.Response.WriteAsJsonAsync(response);
    }
});

// セキュリティヘッダーの設定
app.Use(async (context, next) =>
{
    // 基本的なセキュリティヘッダー
    context.Response.Headers.Append("X-Content-Type-Options", "nosniff");
    context.Response.Headers.Append("X-Frame-Options", "DENY");
    context.Response.Headers.Append("X-XSS-Protection", "1; mode=block");
    context.Response.Headers.Append("Referrer-Policy", "strict-origin-when-cross-origin");
    
    // Content Security Policy (CSP)
    context.Response.Headers.Append("Content-Security-Policy", 
        "default-src 'self'; " +
        "img-src 'self' data: https:; " +
        "font-src 'self' https:; " +
        "style-src 'self' 'unsafe-inline' https:; " +
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " +
        "connect-src 'self' https:; " +
        "frame-ancestors 'none'; " +
        "form-action 'self'; " +
        "base-uri 'self';");

    // Strict Transport Security (HSTS)
    context.Response.Headers.Append("Strict-Transport-Security", "max-age=31536000; includeSubDomains");
    
    // Cache Control
    context.Response.Headers.Append("Cache-Control", "no-store, max-age=0");
    context.Response.Headers.Append("Pragma", "no-cache");
    
    // Cross-Origin Resource Policy
    context.Response.Headers.Append("Cross-Origin-Resource-Policy", "same-origin");
    
    // Cross-Origin Opener Policy
    context.Response.Headers.Append("Cross-Origin-Opener-Policy", "same-origin");
    
    // Cross-Origin Embedder Policy
    context.Response.Headers.Append("Cross-Origin-Embedder-Policy", "require-corp");
    
    // Permissions Policy
    context.Response.Headers.Append("Permissions-Policy", 
        "accelerometer=(), " +
        "camera=(), " +
        "geolocation=(), " +
        "gyroscope=(), " +
        "magnetometer=(), " +
        "microphone=(), " +
        "payment=(), " +
        "usb=()");

    await next();
});

app.MapControllers();

try
{
    Log.Information("Starting web application");
    app.Run();
}
catch (Exception ex)
{
    Log.Fatal(ex, "Application terminated unexpectedly");
}
finally
{
    Log.CloseAndFlush();
}
