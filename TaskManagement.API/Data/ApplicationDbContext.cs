using Microsoft.EntityFrameworkCore;
using TaskManagement.API.Models;

namespace TaskManagement.API.Data
{
    public class ApplicationDbContext : DbContext
    {
        public ApplicationDbContext(DbContextOptions<ApplicationDbContext> options)
            : base(options)
        {
        }

        public DbSet<TaskItem> TaskItems { get; set; }
        public DbSet<Project> Projects { get; set; }
        public DbSet<User> Users { get; set; }

        protected override void OnModelCreating(ModelBuilder modelBuilder)
        {
            base.OnModelCreating(modelBuilder);

            // TaskItem configuration
            modelBuilder.Entity<TaskItem>(entity =>
            {
                entity.Property(t => t.Title)
                    .HasMaxLength(100)
                    .IsRequired()
                    .HasColumnType("varchar(100)");

                entity.Property(t => t.Description)
                    .HasMaxLength(500)
                    .HasColumnType("varchar(500)");

                entity.Property(t => t.IsCompleted)
                    .HasColumnType("tinyint(1)");

                entity.Property(t => t.DueDate)
                    .HasColumnType("datetime");

                entity.Property(t => t.CreatedAt)
                    .HasColumnType("datetime");

                entity.Property(t => t.UpdatedAt)
                    .HasColumnType("datetime");
            });

            // Project configuration
            modelBuilder.Entity<Project>(entity =>
            {
                entity.Property(p => p.Name)
                    .HasMaxLength(100)
                    .IsRequired()
                    .HasColumnType("varchar(100)");

                entity.Property(p => p.Description)
                    .HasMaxLength(500)
                    .HasColumnType("varchar(500)");

                entity.Property(p => p.StartDate)
                    .HasColumnType("datetime");

                entity.Property(p => p.EndDate)
                    .HasColumnType("datetime");

                entity.Property(p => p.CreatedAt)
                    .HasColumnType("datetime");

                entity.Property(p => p.UpdatedAt)
                    .HasColumnType("datetime");
            });

            // User configuration
            modelBuilder.Entity<User>(entity =>
            {
                entity.Property(u => u.Username)
                    .HasMaxLength(50)
                    .IsRequired()
                    .HasColumnType("varchar(50)");

                entity.Property(u => u.Email)
                    .HasMaxLength(100)
                    .IsRequired()
                    .HasColumnType("varchar(100)");

                entity.Property(u => u.PasswordHash)
                    .HasMaxLength(255)
                    .IsRequired()
                    .HasColumnType("varchar(255)");

                entity.Property(u => u.Role)
                    .HasMaxLength(20)
                    .HasColumnType("varchar(20)");

                entity.Property(u => u.CreatedAt)
                    .HasColumnType("datetime");

                entity.Property(u => u.UpdatedAt)
                    .HasColumnType("datetime");
            });

            // Relationships
            modelBuilder.Entity<TaskItem>()
                .HasOne(t => t.Project)
                .WithMany(p => p.Tasks)
                .HasForeignKey(t => t.ProjectId)
                .OnDelete(DeleteBehavior.SetNull);
        }
    }
} 