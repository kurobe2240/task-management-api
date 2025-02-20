using System;
using TaskManagement.API.Models;

namespace TaskManagement.API.DTOs;

public class SearchParameters
{
    private const int MaxPageSize = 50;
    private int _pageSize = 10;

    // ページネーション
    public int PageNumber { get; set; } = 1;
    public int PageSize
    {
        get => _pageSize;
        set => _pageSize = value > MaxPageSize ? MaxPageSize : value;
    }

    // 検索条件
    public string? SearchTerm { get; set; }
    public string? SearchField { get; set; } // title, description, all
    public DateTime? FromDate { get; set; }
    public DateTime? ToDate { get; set; }
    public Priority? Priority { get; set; }
    public Status? Status { get; set; }
    public int? ProjectId { get; set; }
    public string? AssignedToUserId { get; set; }

    // 高度な検索オプション
    public bool IncludeCompleted { get; set; } = true;
    public bool IncludeArchived { get; set; } = false;
    public bool MatchExactPhrase { get; set; } = false;
    public string[]? Tags { get; set; }

    // ソート
    public string? SortBy { get; set; } // createdAt, dueDate, priority, etc.
    public bool IsDescending { get; set; } = false;
}

public class SearchResult<T>
{
    public IEnumerable<T> Items { get; set; } = new List<T>();
    public int TotalCount { get; set; }
    public int PageNumber { get; set; }
    public int PageSize { get; set; }
    public int TotalPages { get; set; }
    public Dictionary<string, int> Facets { get; set; } = new();
    public List<string> Suggestions { get; set; } = new();
    public TimeSpan SearchTime { get; set; }
} 