using System;
using System.Threading.Tasks;
using System.Linq.Expressions;
using System.Collections.Generic;
using System.Linq;
using AutoMapper;
using FluentAssertions;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Moq;
using TaskManagement.API.Controllers;
using TaskManagement.API.Data;
using TaskManagement.API.DTOs;
using TaskManagement.API.Models;
using TaskManagement.API.Services;
using Xunit;

namespace TaskManagement.Tests.Unit.Controllers
{
    public class AuthControllerTests
    {
        private readonly Mock<IAuthService> _mockAuthService;
        private readonly Mock<IMapper> _mockMapper;
        private readonly AuthController _controller;
        private readonly ApplicationDbContext _context;

        public AuthControllerTests()
        {
            var options = new DbContextOptionsBuilder<ApplicationDbContext>()
                .UseInMemoryDatabase(databaseName: Guid.NewGuid().ToString())
                .Options;
            _context = new ApplicationDbContext(options);
            
            _mockAuthService = new Mock<IAuthService>();
            _mockMapper = new Mock<IMapper>();
            
            _controller = new AuthController(
                _context,
                _mockAuthService.Object,
                _mockMapper.Object);
        }

        [Fact]
        public async Task Register_WithValidUser_ReturnsOkResult()
        {
            // Arrange
            var registerDto = new RegisterUserDto
            {
                Username = "testuser",
                Email = "test@example.com",
                Password = "Password123!"
            };

            var user = new User
            {
                Id = 1,
                Username = registerDto.Username,
                Email = registerDto.Email,
                PasswordHash = "hashedpassword"
            };

            var userDto = new UserDto
            {
                Id = 1,
                Username = registerDto.Username,
                Email = registerDto.Email
            };

            _mockAuthService.Setup(auth => auth.HashPassword(registerDto.Password))
                .Returns("hashedpassword");

            _mockAuthService.Setup(auth => auth.GenerateJwtToken(It.IsAny<User>()))
                .Returns("testtoken");

            _mockMapper.Setup(mapper => mapper.Map<UserDto>(It.IsAny<User>()))
                .Returns(userDto);

            // Act
            var result = await _controller.Register(registerDto);

            // Assert
            var okResult = result.Result.Should().BeOfType<OkObjectResult>().Subject;
            var authResponse = okResult.Value.Should().BeOfType<AuthResponseDto>().Subject;
            authResponse.Token.Should().Be("testtoken");
            authResponse.User.Username.Should().Be(registerDto.Username);
        }

        [Fact]
        public async Task Login_WithValidCredentials_ReturnsOkResult()
        {
            // Arrange
            var loginDto = new LoginUserDto
            {
                Username = "testuser",
                Password = "Password123!"
            };

            var user = new User
            {
                Id = 1,
                Username = loginDto.Username,
                PasswordHash = "hashedpassword"
            };

            var userDto = new UserDto
            {
                Id = 1,
                Username = loginDto.Username
            };

            _context.Users.Add(user);
            await _context.SaveChangesAsync();

            _mockAuthService.Setup(auth => auth.VerifyPassword(loginDto.Password, user.PasswordHash))
                .Returns(true);

            _mockAuthService.Setup(auth => auth.GenerateJwtToken(It.IsAny<User>()))
                .Returns("testtoken");

            _mockMapper.Setup(mapper => mapper.Map<UserDto>(It.IsAny<User>()))
                .Returns(userDto);

            // Act
            var result = await _controller.Login(loginDto);

            // Assert
            var okResult = result.Result.Should().BeOfType<OkObjectResult>().Subject;
            var authResponse = okResult.Value.Should().BeOfType<AuthResponseDto>().Subject;
            authResponse.Token.Should().Be("testtoken");
            authResponse.User.Username.Should().Be(loginDto.Username);
        }

        [Fact]
        public async Task Login_WithInvalidCredentials_ReturnsUnauthorized()
        {
            // Arrange
            var loginDto = new LoginUserDto
            {
                Username = "testuser",
                Password = "wrongpassword"
            };

            var user = new User
            {
                Id = 1,
                Username = loginDto.Username,
                PasswordHash = "hashedpassword"
            };

            _context.Users.Add(user);
            await _context.SaveChangesAsync();

            _mockAuthService.Setup(auth => auth.VerifyPassword(loginDto.Password, user.PasswordHash))
                .Returns(false);

            // Act
            var result = await _controller.Login(loginDto);

            // Assert
            result.Result.Should().BeOfType<UnauthorizedObjectResult>();
        }

        [Fact]
        public async Task Register_WithExistingUsername_ReturnsBadRequest()
        {
            // Arrange
            var existingUser = new User
            {
                Username = "existinguser",
                Email = "existing@example.com",
                PasswordHash = "hashedpassword"
            };

            _context.Users.Add(existingUser);
            await _context.SaveChangesAsync();

            var registerDto = new RegisterUserDto
            {
                Username = "existinguser",
                Email = "test@example.com",
                Password = "Password123!"
            };

            // Act
            var result = await _controller.Register(registerDto);

            // Assert
            result.Result.Should().BeOfType<BadRequestObjectResult>();
        }

        [Fact]
        public async Task UpdateCurrentUser_WithValidData_ReturnsNoContent()
        {
            // Arrange
            var userId = 1;
            var updateDto = new UpdateUserDto
            {
                Email = "newemail@example.com"
            };

            var user = new User
            {
                Id = userId,
                Email = "oldemail@example.com",
                Username = "testuser"
            };

            _context.Users.Add(user);
            await _context.SaveChangesAsync();

            // システムのUser.FindFirstを模倣
            var claims = new System.Security.Claims.ClaimsPrincipal(new System.Security.Claims.ClaimsIdentity(
                new System.Security.Claims.Claim[]
                {
                    new System.Security.Claims.Claim(System.Security.Claims.ClaimTypes.NameIdentifier, userId.ToString())
                }));

            _controller.ControllerContext = new ControllerContext
            {
                HttpContext = new Microsoft.AspNetCore.Http.DefaultHttpContext { User = claims }
            };

            // Act
            var result = await _controller.UpdateCurrentUser(updateDto);

            // Assert
            result.Should().BeOfType<NoContentResult>();
        }

        public void Dispose()
        {
            _context.Database.EnsureDeleted();
            _context.Dispose();
        }
    }
} 