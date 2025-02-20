using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;
using TaskManagement.API.Models;
using TaskManagement.API.DTOs;

namespace TaskManagement.API.Data
{
    public class ProjectRepository : IProjectRepository
    {
        private readonly ApplicationDbContext _context;

        public ProjectRepository(ApplicationDbContext context)
        {
            _context = context;
        }

        public async Task<PagedResponse<Project>> GetProjectsAsync(ProjectParameters parameters)
        {
            var query = _context.Projects
                .Include(p => p.Tasks)
                .AsQueryable();

            // 検索
            if (!string.IsNullOrWhiteSpace(parameters.SearchTerm))
            {
                var searchTerm = parameters.SearchTerm.ToLower();
                query = query.Where(p => 
                    p.Name.ToLower().Contains(searchTerm) || 
                    p.Description.ToLower().Contains(searchTerm));
            }

            // フィルタリング
            if (parameters.Status.HasValue)
            {
                query = query.Where(p => p.Status == parameters.Status.Value);
            }

            if (parameters.StartDateFrom.HasValue)
            {
                query = query.Where(p => p.StartDate >= parameters.StartDateFrom.Value);
            }

            if (parameters.StartDateTo.HasValue)
            {
                query = query.Where(p => p.StartDate <= parameters.StartDateTo.Value);
            }

            if (parameters.EndDateFrom.HasValue)
            {
                query = query.Where(p => p.EndDate >= parameters.EndDateFrom.Value);
            }

            if (parameters.EndDateTo.HasValue)
            {
                query = query.Where(p => p.EndDate <= parameters.EndDateTo.Value);
            }

            // ソート
            query = parameters.OrderBy?.ToLower() switch
            {
                "createdat" => parameters.IsDescending 
                    ? query.OrderByDescending(p => p.CreatedAt)
                    : query.OrderBy(p => p.CreatedAt),
                "startdate" => parameters.IsDescending
                    ? query.OrderByDescending(p => p.StartDate)
                    : query.OrderBy(p => p.StartDate),
                "taskcount" => parameters.IsDescending
                    ? query.OrderByDescending(p => p.Tasks.Count)
                    : query.OrderBy(p => p.Tasks.Count),
                _ => query.OrderByDescending(p => p.CreatedAt)
            };

            // 総件数の取得
            var totalCount = await query.CountAsync();
            var totalPages = (int)Math.Ceiling(totalCount / (double)parameters.PageSize);

            // ページネーション
            var items = await query
                .Skip((parameters.PageNumber - 1) * parameters.PageSize)
                .Take(parameters.PageSize)
                .ToListAsync();

            return new PagedResponse<Project>
            {
                Items = items,
                CurrentPage = parameters.PageNumber,
                PageSize = parameters.PageSize,
                TotalCount = totalCount,
                TotalPages = totalPages
            };
        }

        public async Task<Project?> GetProjectByIdAsync(int id)
        {
            return await _context.Projects
                .Include(p => p.Tasks)
                .FirstOrDefaultAsync(p => p.Id == id);
        }

        public async Task<Project> CreateProjectAsync(Project project)
        {
            _context.Projects.Add(project);
            await _context.SaveChangesAsync();
            return project;
        }

        public async Task UpdateProjectAsync(Project project)
        {
            project.UpdatedAt = DateTime.Now;
            _context.Entry(project).State = EntityState.Modified;
            await _context.SaveChangesAsync();
        }

        public async Task DeleteProjectAsync(Project project)
        {
            _context.Projects.Remove(project);
            await _context.SaveChangesAsync();
        }

        public async Task<bool> ProjectExistsAsync(int id)
        {
            return await _context.Projects.AnyAsync(p => p.Id == id);
        }

        public async Task<IEnumerable<TaskItem>> GetProjectTasksAsync(int projectId)
        {
            return await _context.TaskItems
                .Where(t => t.ProjectId == projectId)
                .ToListAsync();
        }
    }
} 