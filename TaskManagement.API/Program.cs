using Microsoft.EntityFrameworkCore;
using Microsoft.OpenApi.Models;
using FluentValidation;
using FluentValidation.AspNetCore;
using TaskManagement.API.Data;
using TaskManagement.API.Profiles;
using TaskManagement.API.Models;
using TaskManagement.API.Validators;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container.
builder.Services.AddControllers();
builder.Services.AddFluentValidationAutoValidation()
    .AddValidatorsFromAssemblyContaining<TaskItemValidator>();

// Database configuration
builder.Services.AddDbContext<ApplicationDbContext>(options =>
{
    options.UseSqlServer(builder.Configuration.GetConnectionString("DefaultConnection"),
        sqlServerOptionsAction: sqlOptions =>
        {
            sqlOptions.EnableRetryOnFailure(
                maxRetryCount: 5,
                maxRetryDelay: TimeSpan.FromSeconds(30),
                errorNumbersToAdd: null);
        });
});

// Repository registration
builder.Services.AddScoped<ITaskRepository, TaskRepository>();

// AutoMapper configuration
builder.Services.AddAutoMapper(typeof(TaskItemProfile));

// CORS configuration
builder.Services.AddCors(options =>
{
    options.AddPolicy("AllowAll",
        builder =>
        {
            builder.AllowAnyOrigin()
                   .AllowAnyMethod()
                   .AllowAnyHeader();
        });
});

// Swagger configuration
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen(c =>
{
    c.SwaggerDoc("v1", new OpenApiInfo { Title = "TaskManagement.API", Version = "v1" });
});

var app = builder.Build();

// Demo data seeding
try
{
    using (var scope = app.Services.CreateScope())
    {
        var dbContext = scope.ServiceProvider.GetRequiredService<ApplicationDbContext>();
        
        // データベースが存在しない場合は作成し、保留中のマイグレーションを適用
        dbContext.Database.EnsureDeleted(); // 開発中は既存のDBを削除（本番環境では使用しない）
        dbContext.Database.EnsureCreated();

        // デモデータの追加
        if (!dbContext.TaskItems.Any())
        {
            Console.WriteLine("デモデータを追加しています...");
            dbContext.TaskItems.AddRange(
                new TaskItem { Title = "デモタスク1", Description = "これはデモタスクです", IsCompleted = false },
                new TaskItem { Title = "デモタスク2", Description = "これはテストタスクです", IsCompleted = true, DueDate = DateTime.Now.AddDays(1) }
            );
            dbContext.SaveChanges();
            Console.WriteLine("デモデータの追加が完了しました。");
        }
    }
}
catch (Exception ex)
{
    Console.WriteLine($"データベース初期化中にエラーが発生しました: {ex.Message}");
    Console.WriteLine($"StackTrace: {ex.StackTrace}");
}

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

app.UseDefaultFiles(); // index.htmlをデフォルトページとして設定
app.UseStaticFiles(); // 静的ファイルの提供を有効化

app.UseCors("AllowAll");
app.UseHttpsRedirection();
app.UseAuthorization();
app.MapControllers();

// 起動時のURLを表示
Console.WriteLine("アプリケーションが起動しました。以下のURLでアクセスできます：");
Console.WriteLine("メインページ: http://localhost:5015");
Console.WriteLine("Swagger UI: http://localhost:5015/swagger");
Console.WriteLine("API エンドポイント: http://localhost:5015/api/TaskItems");

app.Run();
