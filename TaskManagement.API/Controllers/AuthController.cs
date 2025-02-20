using System;
using System.Threading.Tasks;
using AutoMapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using TaskManagement.API.Data;
using TaskManagement.API.DTOs;
using TaskManagement.API.Models;
using TaskManagement.API.Services;

namespace TaskManagement.API.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    public class AuthController : ControllerBase
    {
        private readonly ApplicationDbContext _context;
        private readonly IAuthService _authService;
        private readonly IMapper _mapper;

        public AuthController(
            ApplicationDbContext context,
            IAuthService authService,
            IMapper mapper)
        {
            _context = context;
            _authService = authService;
            _mapper = mapper;
        }

        [HttpPost("register")]
        public async Task<ActionResult<AuthResponseDto>> Register(RegisterUserDto registerDto)
        {
            // ユーザー名の重複チェック
            if (await _context.Users.AnyAsync(u => u.Username == registerDto.Username))
            {
                return BadRequest("このユーザー名は既に使用されています。");
            }

            // メールアドレスの重複チェック
            if (await _context.Users.AnyAsync(u => u.Email == registerDto.Email))
            {
                return BadRequest("このメールアドレスは既に使用されています。");
            }

            // ユーザーの作成
            var user = new User
            {
                Username = registerDto.Username,
                Email = registerDto.Email,
                PasswordHash = _authService.HashPassword(registerDto.Password),
                Role = "User" // デフォルトロール
            };

            _context.Users.Add(user);
            await _context.SaveChangesAsync();

            // トークンの生成
            var token = _authService.GenerateJwtToken(user);

            return Ok(new AuthResponseDto
            {
                Token = token,
                ExpiresAt = DateTime.Now.AddHours(1),
                User = _mapper.Map<UserDto>(user)
            });
        }

        [HttpPost("login")]
        public async Task<ActionResult<AuthResponseDto>> Login(LoginUserDto loginDto)
        {
            if (string.IsNullOrEmpty(loginDto.Username) || string.IsNullOrEmpty(loginDto.Password))
            {
                return BadRequest("ユーザー名とパスワードは必須です。");
            }

            var user = await _context.Users
                .FirstOrDefaultAsync(u => u.Username == loginDto.Username);

            if (user == null)
            {
                return Unauthorized("ユーザー名またはパスワードが正しくありません。");
            }

            if (!_authService.VerifyPassword(loginDto.Password, user.PasswordHash))
            {
                return Unauthorized("ユーザー名またはパスワードが正しくありません。");
            }

            var token = _authService.GenerateJwtToken(user);

            return Ok(new AuthResponseDto
            {
                Token = token,
                ExpiresAt = DateTime.Now.AddHours(1),
                User = _mapper.Map<UserDto>(user)
            });
        }

        [Authorize]
        [HttpGet("me")]
        public async Task<ActionResult<UserDto>> GetCurrentUser()
        {
            var userId = User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)?.Value;
            if (string.IsNullOrEmpty(userId))
            {
                return Unauthorized();
            }

            var user = await _context.Users.FindAsync(int.Parse(userId));
            if (user == null)
            {
                return NotFound();
            }

            return Ok(_mapper.Map<UserDto>(user));
        }

        [Authorize]
        [HttpPut("me")]
        public async Task<IActionResult> UpdateCurrentUser(UpdateUserDto updateDto)
        {
            var userId = User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)?.Value;
            if (string.IsNullOrEmpty(userId))
            {
                return Unauthorized();
            }

            var user = await _context.Users.FindAsync(int.Parse(userId));
            if (user == null)
            {
                return NotFound();
            }

            // メールアドレスの更新
            if (!string.IsNullOrEmpty(updateDto.Email) && updateDto.Email != user.Email)
            {
                if (await _context.Users.AnyAsync(u => u.Email == updateDto.Email))
                {
                    return BadRequest("このメールアドレスは既に使用されています。");
                }
                user.Email = updateDto.Email;
            }

            // パスワードの更新
            if (!string.IsNullOrEmpty(updateDto.NewPassword))
            {
                if (string.IsNullOrEmpty(updateDto.CurrentPassword))
                {
                    return BadRequest("現在のパスワードを入力してください。");
                }

                if (!_authService.VerifyPassword(updateDto.CurrentPassword, user.PasswordHash))
                {
                    return BadRequest("現在のパスワードが正しくありません。");
                }
                user.PasswordHash = _authService.HashPassword(updateDto.NewPassword);
            }

            user.UpdatedAt = DateTime.Now;
            await _context.SaveChangesAsync();

            return NoContent();
        }
    }
} 