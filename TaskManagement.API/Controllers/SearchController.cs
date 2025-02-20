using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using TaskManagement.API.DTOs;
using TaskManagement.API.Services;

namespace TaskManagement.API.Controllers;

[Authorize]
[ApiController]
[Route("api/[controller]")]
public class SearchController : ControllerBase
{
    private readonly ISearchService _searchService;
    private readonly ILogger<SearchController> _logger;

    public SearchController(ISearchService searchService, ILogger<SearchController> logger)
    {
        _searchService = searchService;
        _logger = logger;
    }

    /// <summary>
    /// タスクを検索します。
    /// </summary>
    /// <param name="parameters">検索パラメータ</param>
    /// <returns>検索結果</returns>
    [HttpGet("tasks")]
    public async Task<ActionResult<SearchResult<TaskItemDto>>> SearchTasks([FromQuery] SearchParameters parameters)
    {
        try
        {
            var result = await _searchService.SearchTasksAsync(parameters);
            return Ok(result);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "タスクの検索中にエラーが発生しました");
            return StatusCode(500, "検索中にエラーが発生しました。");
        }
    }

    /// <summary>
    /// プロジェクトを検索します。
    /// </summary>
    /// <param name="parameters">検索パラメータ</param>
    /// <returns>検索結果</returns>
    [HttpGet("projects")]
    public async Task<ActionResult<SearchResult<ProjectDto>>> SearchProjects([FromQuery] SearchParameters parameters)
    {
        try
        {
            var result = await _searchService.SearchProjectsAsync(parameters);
            return Ok(result);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "プロジェクトの検索中にエラーが発生しました");
            return StatusCode(500, "検索中にエラーが発生しました。");
        }
    }
} 