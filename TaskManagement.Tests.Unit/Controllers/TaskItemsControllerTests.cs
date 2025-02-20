using System;
using System.Collections.Generic;
using System.Threading.Tasks;
using System.Linq.Expressions;
using AutoMapper;
using FluentAssertions;
using Microsoft.AspNetCore.Mvc;
using Moq;
using TaskManagement.API.Controllers;
using TaskManagement.API.Data;
using TaskManagement.API.DTOs;
using TaskManagement.API.Models;
using TaskManagement.API.Services;
using Xunit;

namespace TaskManagement.Tests.Unit.Controllers
{
    public class TaskItemsControllerTests
    {
        private readonly Mock<ITaskRepository> _mockTaskRepo;
        private readonly Mock<IMapper> _mockMapper;
        private readonly TaskItemsController _controller;

        public TaskItemsControllerTests()
        {
            _mockTaskRepo = new Mock<ITaskRepository>();
            _mockMapper = new Mock<IMapper>();
            _controller = new TaskItemsController(
                _mockTaskRepo.Object,
                _mockMapper.Object);
        }

        [Fact]
        public async Task GetTaskItems_ReturnsOkResult_WithTaskItems()
        {
            // Arrange
            var taskItems = new List<TaskItem>
            {
                new TaskItem { Id = 1, Title = "Test Task 1" },
                new TaskItem { Id = 2, Title = "Test Task 2" }
            };

            var taskItemDtos = new List<TaskItemDto>
            {
                new TaskItemDto { Id = 1, Title = "Test Task 1" },
                new TaskItemDto { Id = 2, Title = "Test Task 2" }
            };

            _mockTaskRepo.Setup(repo => repo.GetAllAsync())
                .ReturnsAsync(taskItems);

            _mockMapper.Setup(mapper => mapper.Map<IEnumerable<TaskItemDto>>(taskItems))
                .Returns(taskItemDtos);

            // Act
            var result = await _controller.GetTaskItems();

            // Assert
            var okResult = result.Result.Should().BeOfType<OkObjectResult>().Subject;
            var returnedTaskItems = okResult.Value.Should().BeAssignableTo<IEnumerable<TaskItemDto>>().Subject;
            returnedTaskItems.Should().HaveCount(2);
        }

        [Fact]
        public async Task GetTaskItem_WithValidId_ReturnsOkResult()
        {
            // Arrange
            var taskId = 1;
            var taskItem = new TaskItem { Id = taskId, Title = "Test Task" };
            var taskItemDto = new TaskItemDto { Id = taskId, Title = "Test Task" };

            _mockTaskRepo.Setup(repo => repo.GetByIdAsync(taskId))
                .ReturnsAsync(taskItem);

            _mockMapper.Setup(mapper => mapper.Map<TaskItemDto>(taskItem))
                .Returns(taskItemDto);

            // Act
            var result = await _controller.GetTaskItem(taskId);

            // Assert
            var okResult = result.Result.Should().BeOfType<OkObjectResult>().Subject;
            var returnedTaskItem = okResult.Value.Should().BeOfType<TaskItemDto>().Subject;
            returnedTaskItem.Id.Should().Be(taskId);
        }

        [Fact]
        public async Task GetTaskItem_WithInvalidId_ReturnsNotFound()
        {
            // Arrange
            var taskId = 999;
            _mockTaskRepo.Setup(repo => repo.GetByIdAsync(taskId))
                .ReturnsAsync((TaskItem?)null);

            // Act
            var result = await _controller.GetTaskItem(taskId);

            // Assert
            result.Result.Should().BeOfType<NotFoundResult>();
        }

        [Fact]
        public async Task CreateTaskItem_WithValidTask_ReturnsCreatedAtAction()
        {
            // Arrange
            var taskItemDto = new TaskItemDto { Title = "New Task" };
            var taskItem = new TaskItem { Id = 1, Title = "New Task" };

            _mockMapper.Setup(mapper => mapper.Map<TaskItem>(taskItemDto))
                .Returns(taskItem);

            _mockTaskRepo.Setup(repo => repo.CreateAsync(taskItem))
                .ReturnsAsync(taskItem);

            _mockMapper.Setup(mapper => mapper.Map<TaskItemDto>(taskItem))
                .Returns(taskItemDto);

            // Act
            var result = await _controller.CreateTaskItem(taskItemDto);

            // Assert
            var createdAtActionResult = result.Result.Should().BeOfType<CreatedAtActionResult>().Subject;
            createdAtActionResult.ActionName.Should().Be(nameof(TaskItemsController.GetTaskItem));
            var returnedTaskItem = createdAtActionResult.Value.Should().BeOfType<TaskItemDto>().Subject;
            returnedTaskItem.Title.Should().Be(taskItemDto.Title);
        }

        [Fact]
        public async Task UpdateTaskItem_WithValidTask_ReturnsNoContent()
        {
            // Arrange
            var taskId = 1;
            var taskItemDto = new TaskItemDto { Id = taskId, Title = "Updated Task" };
            var existingTask = new TaskItem { Id = taskId, Title = "Original Task" };
            var updatedTask = new TaskItem { Id = taskId, Title = "Updated Task" };

            _mockTaskRepo.Setup(repo => repo.GetByIdAsync(taskId))
                .ReturnsAsync(existingTask);

            _mockMapper.Setup(mapper => mapper.Map(taskItemDto, existingTask))
                .Returns(updatedTask);

            _mockTaskRepo.Setup(repo => repo.UpdateAsync(It.IsAny<TaskItem>()))
                .Returns(Task.FromResult(true));

            // Act
            var result = await _controller.UpdateTaskItem(taskId, taskItemDto);

            // Assert
            result.Should().BeOfType<NoContentResult>();
            _mockTaskRepo.Verify(repo => repo.UpdateAsync(It.Is<TaskItem>(t => 
                t != null && t.Id == taskId && t.Title == updatedTask.Title)), Times.Once);
        }

        [Fact]
        public async Task DeleteTaskItem_WithValidId_ReturnsNoContent()
        {
            // Arrange
            var taskId = 1;
            var existingTask = new TaskItem { Id = taskId, Title = "Task to Delete" };

            _mockTaskRepo.Setup(repo => repo.GetByIdAsync(taskId))
                .ReturnsAsync(existingTask);

            _mockTaskRepo.Setup(repo => repo.DeleteAsync(taskId))
                .Returns(Task.FromResult(true));

            // Act
            var result = await _controller.DeleteTaskItem(taskId);

            // Assert
            result.Should().BeOfType<NoContentResult>();
            _mockTaskRepo.Verify(repo => repo.DeleteAsync(taskId), Times.Once);
        }
    }
} 