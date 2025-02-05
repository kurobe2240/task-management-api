using FluentValidation;
using TaskManagement.API.DTOs;

namespace TaskManagement.API.Validators
{
    public class TaskItemValidator : AbstractValidator<TaskItemDto>
    {
        public TaskItemValidator()
        {
            RuleFor(x => x.Title)
                .NotEmpty().WithMessage("Title is required.")
                .MaximumLength(100).WithMessage("Title cannot exceed 100 characters.");

            RuleFor(x => x.Description)
                .NotEmpty().WithMessage("Description is required.")
                .MaximumLength(500).WithMessage("Description cannot exceed 500 characters.");
        }
    }
} 