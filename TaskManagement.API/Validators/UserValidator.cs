using FluentValidation;
using TaskManagement.API.DTOs;

namespace TaskManagement.API.Validators
{
    public class RegisterUserValidator : AbstractValidator<RegisterUserDto>
    {
        public RegisterUserValidator()
        {
            RuleFor(x => x.Username)
                .NotEmpty().WithMessage("ユーザー名は必須です。")
                .MinimumLength(3).WithMessage("ユーザー名は3文字以上で入力してください。")
                .MaximumLength(50).WithMessage("ユーザー名は50文字以内で入力してください。");

            RuleFor(x => x.Email)
                .NotEmpty().WithMessage("メールアドレスは必須です。")
                .EmailAddress().WithMessage("有効なメールアドレスを入力してください。")
                .MaximumLength(100).WithMessage("メールアドレスは100文字以内で入力してください。");

            RuleFor(x => x.Password)
                .NotEmpty().WithMessage("パスワードは必須です。")
                .MinimumLength(8).WithMessage("パスワードは8文字以上で入力してください。")
                .MaximumLength(100).WithMessage("パスワードは100文字以内で入力してください。")
                .Matches("[A-Z]").WithMessage("パスワードには大文字を含める必要があります。")
                .Matches("[a-z]").WithMessage("パスワードには小文字を含める必要があります。")
                .Matches("[0-9]").WithMessage("パスワードには数字を含める必要があります。")
                .Matches("[^a-zA-Z0-9]").WithMessage("パスワードには特殊文字を含める必要があります。");

            RuleFor(x => x.ConfirmPassword)
                .NotEmpty().WithMessage("パスワード（確認）は必須です。")
                .Equal(x => x.Password).WithMessage("パスワードが一致しません。");
        }
    }

    public class LoginUserValidator : AbstractValidator<LoginUserDto>
    {
        public LoginUserValidator()
        {
            RuleFor(x => x.Username)
                .NotEmpty().WithMessage("ユーザー名は必須です。");

            RuleFor(x => x.Password)
                .NotEmpty().WithMessage("パスワードは必須です。");
        }
    }

    public class UpdateUserValidator : AbstractValidator<UpdateUserDto>
    {
        public UpdateUserValidator()
        {
            RuleFor(x => x.Email)
                .EmailAddress().WithMessage("有効なメールアドレスを入力してください。")
                .MaximumLength(100).WithMessage("メールアドレスは100文字以内で入力してください。")
                .When(x => !string.IsNullOrEmpty(x.Email));

            When(x => !string.IsNullOrEmpty(x.NewPassword), () =>
            {
                RuleFor(x => x.CurrentPassword)
                    .NotEmpty().WithMessage("現在のパスワードは必須です。");

                RuleFor(x => x.NewPassword)
                    .NotEmpty().WithMessage("新しいパスワードは必須です。")
                    .MinimumLength(8).WithMessage("パスワードは8文字以上で入力してください。")
                    .MaximumLength(100).WithMessage("パスワードは100文字以内で入力してください。")
                    .Matches("[A-Z]").WithMessage("パスワードには大文字を含める必要があります。")
                    .Matches("[a-z]").WithMessage("パスワードには小文字を含める必要があります。")
                    .Matches("[0-9]").WithMessage("パスワードには数字を含める必要があります。")
                    .Matches("[^a-zA-Z0-9]").WithMessage("パスワードには特殊文字を含める必要があります。");

                RuleFor(x => x.ConfirmNewPassword)
                    .NotEmpty().WithMessage("新しいパスワード（確認）は必須です。")
                    .Equal(x => x.NewPassword).WithMessage("新しいパスワードが一致しません。");
            });
        }
    }
} 