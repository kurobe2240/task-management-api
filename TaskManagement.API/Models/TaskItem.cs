using System;

namespace TaskManagement.API.Models
{
    public class TaskItem
    {
        public int Id { get; set; }
        public string Title { get; set; } = string.Empty;
        public string Description { get; set; } = string.Empty;
        public bool IsCompleted { get; set; }
        public DateTime? DueDate { get; set; }
        public Priority Priority { get; set; }
        public Status Status { get; set; }
        public DateTime CreatedAt { get; set; } = DateTime.Now;
        public DateTime? UpdatedAt { get; set; }

        // Project reference
        public int? ProjectId { get; set; }
        public Project? Project { get; set; }
    }

    public enum Priority
    {
        Low,
        Medium,
        High,
        Urgent
    }

    public enum Status
    {
        NotStarted,
        InProgress,
        OnHold,
        Completed,
        Cancelled
    }
} 