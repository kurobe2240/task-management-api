using System;
using System.Collections.Generic;
using System.Threading.Tasks;
using AutoMapper;
using Microsoft.AspNetCore.Mvc;
using TaskManagement.API.Data;
using TaskManagement.API.DTOs;
using TaskManagement.API.Models;

namespace TaskManagement.API.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    public class ProjectsController : ControllerBase
    {
        private readonly IProjectRepository _repository;
        private readonly IMapper _mapper;

        public ProjectsController(IProjectRepository repository, IMapper mapper)
        {
            _repository = repository;
            _mapper = mapper;
        }

        /// <summary>
        /// プロジェクト一覧を取得します。
        /// </summary>
        /// <param name="parameters">検索パラメータ</param>
        /// <returns>プロジェクト一覧</returns>
        [HttpGet]
        public async Task<ActionResult<PagedResponse<ProjectDto>>> GetProjects([FromQuery] ProjectParameters parameters)
        {
            var projectPage = await _repository.GetProjectsAsync(parameters);
            
            var response = new PagedResponse<ProjectDto>
            {
                Items = _mapper.Map<IEnumerable<ProjectDto>>(projectPage.Items),
                CurrentPage = projectPage.CurrentPage,
                TotalPages = projectPage.TotalPages,
                PageSize = projectPage.PageSize,
                TotalCount = projectPage.TotalCount
            };

            return Ok(response);
        }

        [HttpGet("{id}")]
        public async Task<ActionResult<ProjectDto>> GetProject(int id)
        {
            var project = await _repository.GetProjectByIdAsync(id);
            if (project == null)
            {
                return NotFound();
            }

            return Ok(_mapper.Map<ProjectDto>(project));
        }

        [HttpPost]
        public async Task<ActionResult<ProjectDto>> CreateProject(CreateProjectDto createProjectDto)
        {
            var project = _mapper.Map<Project>(createProjectDto);
            await _repository.CreateProjectAsync(project);

            var projectDto = _mapper.Map<ProjectDto>(project);
            return CreatedAtAction(nameof(GetProject), new { id = project.Id }, projectDto);
        }

        [HttpPut("{id}")]
        public async Task<IActionResult> UpdateProject(int id, UpdateProjectDto updateProjectDto)
        {
            var project = await _repository.GetProjectByIdAsync(id);
            if (project == null)
            {
                return NotFound();
            }

            _mapper.Map(updateProjectDto, project);
            await _repository.UpdateProjectAsync(project);

            return NoContent();
        }

        [HttpDelete("{id}")]
        public async Task<IActionResult> DeleteProject(int id)
        {
            var project = await _repository.GetProjectByIdAsync(id);
            if (project == null)
            {
                return NotFound();
            }

            await _repository.DeleteProjectAsync(project);
            return NoContent();
        }

        [HttpGet("{id}/tasks")]
        public async Task<ActionResult<IEnumerable<TaskItemDto>>> GetProjectTasks(int id)
        {
            if (!await _repository.ProjectExistsAsync(id))
            {
                return NotFound();
            }

            var tasks = await _repository.GetProjectTasksAsync(id);
            return Ok(_mapper.Map<IEnumerable<TaskItemDto>>(tasks));
        }
    }
} 