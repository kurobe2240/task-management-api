using System.Collections.Generic;
using System.Threading.Tasks;
using TaskManagement.API.Models;
using TaskManagement.API.DTOs;

namespace TaskManagement.API.Data
{
    public interface IProjectRepository
    {
        Task<PagedResponse<Project>> GetProjectsAsync(ProjectParameters parameters);
        Task<Project?> GetProjectByIdAsync(int id);
        Task<Project> CreateProjectAsync(Project project);
        Task UpdateProjectAsync(Project project);
        Task DeleteProjectAsync(Project project);
        Task<bool> ProjectExistsAsync(int id);
        Task<IEnumerable<TaskItem>> GetProjectTasksAsync(int projectId);
    }
} 