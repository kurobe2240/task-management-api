using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Elasticsearch.Net;
using Microsoft.Extensions.Options;
using Microsoft.Extensions.Logging;
using Nest;
using TaskManagement.API.DTOs;
using TaskManagement.API.Models;
using TaskManagement.API.Settings;

namespace TaskManagement.API.Services;

public interface ISearchService
{
    Task<SearchResult<TaskItemDto>> SearchTasksAsync(SearchParameters parameters);
    Task<SearchResult<ProjectDto>> SearchProjectsAsync(SearchParameters parameters);
    Task IndexTaskAsync(TaskItem task);
    Task IndexProjectAsync(Project project);
    Task DeleteTaskFromIndexAsync(int taskId);
    Task DeleteProjectFromIndexAsync(int projectId);
}

public class SearchService : ISearchService
{
    private readonly IElasticClient _elasticClient;
    private readonly ILogger<SearchService> _logger;

    public SearchService(IElasticClient elasticClient, IOptions<ElasticSearchSettings> settings, ILogger<SearchService> logger)
    {
        _elasticClient = elasticClient ?? throw new ArgumentNullException(nameof(elasticClient));
        _logger = logger ?? throw new ArgumentNullException(nameof(logger));

        if (settings == null)
        {
            throw new ArgumentNullException(nameof(settings));
        }

        if (string.IsNullOrEmpty(settings.Value.Url))
        {
            throw new ArgumentException("Elasticsearch URL is not configured.", nameof(settings));
        }

        if (string.IsNullOrEmpty(settings.Value.DefaultIndex))
        {
            throw new ArgumentException("Default index is not configured.", nameof(settings));
        }
    }

    public async Task<SearchResult<TaskItemDto>> SearchTasksAsync(SearchParameters parameters)
    {
        try
        {
            var searchDescriptor = new SearchDescriptor<TaskItem>()
                .From((parameters.PageNumber - 1) * parameters.PageSize)
                .Size(parameters.PageSize);

            // クエリの構築
            var query = BuildTaskSearchQuery(parameters);
            searchDescriptor = searchDescriptor.Query(q => query);

            // ソートの設定
            if (!string.IsNullOrEmpty(parameters.SortBy))
            {
                searchDescriptor = AddSorting(searchDescriptor, parameters);
            }

            // ファセットの追加
            searchDescriptor = AddTaskFacets(searchDescriptor);

            // 検索の実行
            var searchResponse = await _elasticClient.SearchAsync<TaskItem>(searchDescriptor);

            if (!searchResponse.IsValid)
            {
                _logger.LogError("検索中にエラーが発生しました: {Error}", searchResponse.DebugInformation);
                throw new Exception("検索の実行中にエラーが発生しました。");
            }

            return new SearchResult<TaskItemDto>
            {
                Items = searchResponse.Hits.Select(hit => MapToTaskItemDto(hit.Source)),
                TotalCount = (int)searchResponse.Total,
                PageNumber = parameters.PageNumber,
                PageSize = parameters.PageSize,
                TotalPages = (int)Math.Ceiling(searchResponse.Total / (double)parameters.PageSize),
                Facets = ExtractFacets(searchResponse),
                SearchTime = TimeSpan.FromMilliseconds(searchResponse.Took)
            };
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "タスクの検索中にエラーが発生しました");
            throw;
        }
    }

    public async Task<SearchResult<ProjectDto>> SearchProjectsAsync(SearchParameters parameters)
    {
        try
        {
            var searchDescriptor = new SearchDescriptor<Project>()
                .From((parameters.PageNumber - 1) * parameters.PageSize)
                .Size(parameters.PageSize);

            // クエリの構築
            var query = BuildProjectSearchQuery(parameters);
            searchDescriptor = searchDescriptor.Query(q => query);

            // ソートの設定
            if (!string.IsNullOrEmpty(parameters.SortBy))
            {
                searchDescriptor = AddSorting(searchDescriptor, parameters);
            }

            // ファセットの追加
            searchDescriptor = AddProjectFacets(searchDescriptor);

            // 検索の実行
            var searchResponse = await _elasticClient.SearchAsync<Project>(searchDescriptor);

            if (!searchResponse.IsValid)
            {
                _logger.LogError("検索中にエラーが発生しました: {Error}", searchResponse.DebugInformation);
                throw new Exception("検索の実行中にエラーが発生しました。");
            }

            return new SearchResult<ProjectDto>
            {
                Items = searchResponse.Hits.Select(hit => MapToProjectDto(hit.Source)),
                TotalCount = (int)searchResponse.Total,
                PageNumber = parameters.PageNumber,
                PageSize = parameters.PageSize,
                TotalPages = (int)Math.Ceiling(searchResponse.Total / (double)parameters.PageSize),
                Facets = ExtractFacets(searchResponse),
                SearchTime = TimeSpan.FromMilliseconds(searchResponse.Took)
            };
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "プロジェクトの検索中にエラーが発生しました");
            throw;
        }
    }

    public async Task IndexTaskAsync(TaskItem task)
    {
        try
        {
            var response = await _elasticClient.IndexDocumentAsync(task);
            if (!response.IsValid)
            {
                _logger.LogError("タスクのインデックス作成中にエラーが発生しました: {Error}", response.DebugInformation);
                throw new Exception("タスクのインデックス作成中にエラーが発生しました。");
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "タスク {TaskId} のインデックス作成中にエラーが発生しました", task.Id);
            throw;
        }
    }

    public async Task IndexProjectAsync(Project project)
    {
        try
        {
            var response = await _elasticClient.IndexDocumentAsync(project);
            if (!response.IsValid)
            {
                _logger.LogError("プロジェクトのインデックス作成中にエラーが発生しました: {Error}", response.DebugInformation);
                throw new Exception("プロジェクトのインデックス作成中にエラーが発生しました。");
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "プロジェクト {ProjectId} のインデックス作成中にエラーが発生しました", project.Id);
            throw;
        }
    }

    public async Task DeleteTaskFromIndexAsync(int taskId)
    {
        try
        {
            var response = await _elasticClient.DeleteAsync<TaskItem>(taskId);
            if (!response.IsValid)
            {
                _logger.LogError("タスクの削除中にエラーが発生しました: {Error}", response.DebugInformation);
                throw new Exception("タスクの削除中にエラーが発生しました。");
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "タスク {TaskId} の削除中にエラーが発生しました", taskId);
            throw;
        }
    }

    public async Task DeleteProjectFromIndexAsync(int projectId)
    {
        try
        {
            var response = await _elasticClient.DeleteAsync<Project>(projectId);
            if (!response.IsValid)
            {
                _logger.LogError("プロジェクトの削除中にエラーが発生しました: {Error}", response.DebugInformation);
                throw new Exception("プロジェクトの削除中にエラーが発生しました。");
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "プロジェクト {ProjectId} の削除中にエラーが発生しました", projectId);
            throw;
        }
    }

    private QueryContainer BuildTaskSearchQuery(SearchParameters parameters)
    {
        var queries = new List<QueryContainer>();

        // 検索語句の処理
        if (!string.IsNullOrEmpty(parameters.SearchTerm))
        {
            if (parameters.MatchExactPhrase)
            {
                queries.Add(new MatchPhraseQuery
                {
                    Field = parameters.SearchField switch
                    {
                        "title" => Infer.Field<TaskItem>(f => f.Title),
                        "description" => Infer.Field<TaskItem>(f => f.Description),
                        _ => "_all"
                    },
                    Query = parameters.SearchTerm
                });
            }
            else
            {
                queries.Add(new MultiMatchQuery
                {
                    Fields = parameters.SearchField switch
                    {
                        "title" => new[] { "title^3" },
                        "description" => new[] { "description" },
                        _ => new[] { "title^3", "description" }
                    },
                    Query = parameters.SearchTerm,
                    Type = TextQueryType.BestFields,
                    Fuzziness = Fuzziness.Auto
                });
            }
        }

        // フィルターの追加
        if (parameters.FromDate.HasValue)
        {
            queries.Add(new DateRangeQuery
            {
                Field = Infer.Field<TaskItem>(f => f.CreatedAt),
                GreaterThanOrEqualTo = parameters.FromDate.Value
            });
        }

        if (parameters.ToDate.HasValue)
        {
            queries.Add(new DateRangeQuery
            {
                Field = Infer.Field<TaskItem>(f => f.CreatedAt),
                LessThanOrEqualTo = parameters.ToDate.Value
            });
        }

        if (parameters.Priority.HasValue)
        {
            queries.Add(new TermQuery
            {
                Field = Infer.Field<TaskItem>(f => f.Priority),
                Value = parameters.Priority.Value
            });
        }

        if (parameters.Status.HasValue)
        {
            queries.Add(new TermQuery
            {
                Field = Infer.Field<TaskItem>(f => f.Status),
                Value = parameters.Status.Value
            });
        }

        if (parameters.ProjectId.HasValue)
        {
            queries.Add(new TermQuery
            {
                Field = Infer.Field<TaskItem>(f => f.ProjectId),
                Value = parameters.ProjectId.Value
            });
        }

        if (!parameters.IncludeCompleted)
        {
            queries.Add(new TermQuery
            {
                Field = Infer.Field<TaskItem>(f => f.IsCompleted),
                Value = false
            });
        }

        return new BoolQuery { Must = queries };
    }

    private QueryContainer BuildProjectSearchQuery(SearchParameters parameters)
    {
        var queries = new List<QueryContainer>();

        // 検索語句の処理
        if (!string.IsNullOrEmpty(parameters.SearchTerm))
        {
            if (parameters.MatchExactPhrase)
            {
                queries.Add(new MatchPhraseQuery
                {
                    Field = parameters.SearchField switch
                    {
                        "name" => Infer.Field<Project>(f => f.Name),
                        "description" => Infer.Field<Project>(f => f.Description),
                        _ => "_all"
                    },
                    Query = parameters.SearchTerm
                });
            }
            else
            {
                queries.Add(new MultiMatchQuery
                {
                    Fields = parameters.SearchField switch
                    {
                        "name" => new[] { "name^3" },
                        "description" => new[] { "description" },
                        _ => new[] { "name^3", "description" }
                    },
                    Query = parameters.SearchTerm,
                    Type = TextQueryType.BestFields,
                    Fuzziness = Fuzziness.Auto
                });
            }
        }

        // フィルターの追加
        if (parameters.FromDate.HasValue)
        {
            queries.Add(new DateRangeQuery
            {
                Field = Infer.Field<Project>(f => f.StartDate),
                GreaterThanOrEqualTo = parameters.FromDate.Value
            });
        }

        if (parameters.ToDate.HasValue)
        {
            queries.Add(new DateRangeQuery
            {
                Field = Infer.Field<Project>(f => f.EndDate),
                LessThanOrEqualTo = parameters.ToDate.Value
            });
        }

        return new BoolQuery { Must = queries };
    }

    private SearchDescriptor<T> AddSorting<T>(SearchDescriptor<T> descriptor, SearchParameters parameters) where T : class
    {
        return descriptor.Sort(s =>
        {
            if (parameters.SortBy?.Equals("createdAt", StringComparison.OrdinalIgnoreCase) == true)
            {
                return s.Field(f => f
                    .Field("createdAt")
                    .Order(parameters.IsDescending ? SortOrder.Descending : SortOrder.Ascending));
            }
            else if (parameters.SortBy?.Equals("dueDate", StringComparison.OrdinalIgnoreCase) == true)
            {
                return s.Field(f => f
                    .Field("dueDate")
                    .Order(parameters.IsDescending ? SortOrder.Descending : SortOrder.Ascending));
            }
            else if (parameters.SortBy?.Equals("priority", StringComparison.OrdinalIgnoreCase) == true)
            {
                return s.Field(f => f
                    .Field("priority")
                    .Order(parameters.IsDescending ? SortOrder.Descending : SortOrder.Ascending));
            }
            
            // デフォルトは作成日時の降順
            return s.Field(f => f
                .Field("createdAt")
                .Order(SortOrder.Descending));
        });
    }

    private SearchDescriptor<TaskItem> AddTaskFacets(SearchDescriptor<TaskItem> descriptor)
    {
        return descriptor.Aggregations(a => a
            .Terms("priorities", t => t.Field(f => f.Priority))
            .Terms("statuses", t => t.Field(f => f.Status))
            .Terms("projects", t => t.Field(f => f.ProjectId)));
    }

    private SearchDescriptor<Project> AddProjectFacets(SearchDescriptor<Project> descriptor)
    {
        return descriptor.Aggregations(a => a
            .Terms("statuses", t => t.Field(f => f.Status)));
    }

    private Dictionary<string, int> ExtractFacets<T>(ISearchResponse<T> response) where T : class
    {
        var facets = new Dictionary<string, int>();
        if (response.Aggregations == null) return facets;

        var statusAgg = response.Aggregations.Terms("status");
        if (statusAgg?.Buckets != null)
        {
            foreach (var bucket in statusAgg.Buckets)
            {
                if (bucket.Key != null && bucket.DocCount.HasValue)
                {
                    facets.Add(bucket.Key, (int)bucket.DocCount.Value);
                }
            }
        }

        var priorityAgg = response.Aggregations.Terms("priority");
        if (priorityAgg?.Buckets != null)
        {
            foreach (var bucket in priorityAgg.Buckets)
            {
                if (bucket.Key != null && bucket.DocCount.HasValue)
                {
                    facets.Add(bucket.Key, (int)bucket.DocCount.Value);
                }
            }
        }

        return facets;
    }

    private TaskItemDto MapToTaskItemDto(TaskItem task)
    {
        return new TaskItemDto
        {
            Id = task.Id,
            Title = task.Title,
            Description = task.Description,
            IsCompleted = task.IsCompleted,
            DueDate = task.DueDate,
            Priority = task.Priority,
            Status = task.Status,
            CreatedAt = task.CreatedAt,
            UpdatedAt = task.UpdatedAt
        };
    }

    private ProjectDto MapToProjectDto(Project project)
    {
        return new ProjectDto
        {
            Id = project.Id,
            Name = project.Name,
            Description = project.Description,
            StartDate = project.StartDate,
            EndDate = project.EndDate,
            Status = project.Status,
            CreatedAt = project.CreatedAt,
            UpdatedAt = project.UpdatedAt
        };
    }
} 