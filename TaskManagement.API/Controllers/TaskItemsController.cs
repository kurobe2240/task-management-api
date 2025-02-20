using System.Collections.Generic;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Mvc;
using TaskManagement.API.Data;
using TaskManagement.API.Models;
using TaskManagement.API.DTOs;
using AutoMapper;

namespace TaskManagement.API.Controllers
{
    [Route("api/[controller]")]
    [ApiController]
    public class TaskItemsController : ControllerBase
    {
        private readonly ITaskRepository _repository;
        private readonly IMapper _mapper;

        public TaskItemsController(ITaskRepository repository, IMapper mapper)
        {
            _repository = repository;
            _mapper = mapper;
        }

        [HttpGet]
        public async Task<ActionResult<IEnumerable<TaskItemDto>>> GetTaskItems()
        {
            var taskItems = await _repository.GetAllAsync();
            var taskItemsDto = _mapper.Map<IEnumerable<TaskItemDto>>(taskItems);
            return Ok(taskItemsDto);
        }

        [HttpGet("{id}")]
        public async Task<ActionResult<TaskItemDto>> GetTaskItem(int id)
        {
            var taskItem = await _repository.GetByIdAsync(id);
            if (taskItem == null)
            {
                return NotFound();
            }
            var taskItemDto = _mapper.Map<TaskItemDto>(taskItem);
            return Ok(taskItemDto);
        }

        [HttpPost]
        public async Task<ActionResult<TaskItemDto>> CreateTaskItem(TaskItemDto taskItemDto)
        {
            var taskItem = _mapper.Map<TaskItem>(taskItemDto);
            await _repository.CreateAsync(taskItem);
            var createdTaskItemDto = _mapper.Map<TaskItemDto>(taskItem);
            return CreatedAtAction(nameof(GetTaskItem), new { id = createdTaskItemDto.Id }, createdTaskItemDto);
        }

        [HttpPut("{id}")]
        public async Task<IActionResult> UpdateTaskItem(int id, TaskItemDto taskItemDto)
        {
            if (id != taskItemDto.Id)
            {
                return BadRequest();
            }

            var existingTask = await _repository.GetByIdAsync(id);
            if (existingTask == null)
            {
                return NotFound();
            }

            var updatedTask = _mapper.Map(taskItemDto, existingTask);
            await _repository.UpdateAsync(updatedTask);
            return NoContent();
        }

        [HttpDelete("{id}")]
        public async Task<IActionResult> DeleteTaskItem(int id)
        {
            await _repository.DeleteAsync(id);
            return NoContent();
        }
    }
} 