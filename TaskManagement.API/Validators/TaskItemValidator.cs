using FluentValidation;
using TaskManagement.API.DTOs;

namespace TaskManagement.API.Validators
{
    public class TaskItemValidator : AbstractValidator<TaskItemDto>
    {
        public TaskItemValidator()
        {
            RuleFor(x => x.Title)
                .NotEmpty().WithMessage("タイトルは必須です。")
                .MaximumLength(100).WithMessage("タイトルは100文字以内で入力してください。");

            RuleFor(x => x.Description)
                .NotEmpty().WithMessage("Description is required.")
                .MaximumLength(500).WithMessage("Description cannot exceed 500 characters.");

            RuleFor(x => x.DueDate)
                .Must(x => x == null || x > DateTime.Now)
                .WithMessage("期限は現在時刻より後の日時を指定してください。");

            RuleFor(x => x.Priority)
                .IsInEnum().WithMessage("無効な優先度が指定されています。");

            RuleFor(x => x.Status)
                .IsInEnum().WithMessage("無効なステータスが指定されています。");
        }
    }
} 