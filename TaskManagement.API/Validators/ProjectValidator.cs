using FluentValidation;
using TaskManagement.API.DTOs;

namespace TaskManagement.API.Validators
{
    public class CreateProjectValidator : AbstractValidator<CreateProjectDto>
    {
        public CreateProjectValidator()
        {
            RuleFor(x => x.Name)
                .NotEmpty().WithMessage("プロジェクト名は必須です。")
                .MaximumLength(100).WithMessage("プロジェクト名は100文字以内で入力してください。");

            RuleFor(x => x.Description)
                .MaximumLength(500).WithMessage("説明は500文字以内で入力してください。");

            RuleFor(x => x.StartDate)
                .NotEmpty().WithMessage("開始日は必須です。");

            RuleFor(x => x.EndDate)
                .Must((project, endDate) => endDate == null || endDate > project.StartDate)
                .WithMessage("終了日は開始日より後の日付を指定してください。");

            RuleFor(x => x.Status)
                .IsInEnum().WithMessage("無効なステータスが指定されています。");
        }
    }

    public class UpdateProjectValidator : AbstractValidator<UpdateProjectDto>
    {
        public UpdateProjectValidator()
        {
            RuleFor(x => x.Name)
                .NotEmpty().WithMessage("プロジェクト名は必須です。")
                .MaximumLength(100).WithMessage("プロジェクト名は100文字以内で入力してください。");

            RuleFor(x => x.Description)
                .MaximumLength(500).WithMessage("説明は500文字以内で入力してください。");

            RuleFor(x => x.StartDate)
                .NotEmpty().WithMessage("開始日は必須です。");

            RuleFor(x => x.EndDate)
                .Must((project, endDate) => endDate == null || endDate > project.StartDate)
                .WithMessage("終了日は開始日より後の日付を指定してください。");

            RuleFor(x => x.Status)
                .IsInEnum().WithMessage("無効なステータスが指定されています。");
        }
    }
} 