import React, { useState, useEffect } from 'react';
import {
  Box,
  Paper,
  Typography,
  List,
  ListItem,
  ListItemText,
  IconButton,
  Chip,
  TextField,
  CircularProgress,
  Alert,
  Card,
  CardContent,
  CardActions,
  Button,
} from '@mui/material';
import {
  Edit as EditIcon,
  Delete as DeleteIcon,
  Add as AddIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import axiosInstance from '../lib/axios';

interface Task {
  id: number;
  title: string;
  status: string;
}

interface Project {
  id: number;
  name: string;
  description: string;
  status: 'active' | 'completed' | 'on_hold';
  tasks: Task[];
}

const ProjectList = () => {
  const navigate = useNavigate();
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  const fetchProjects = async () => {
    try {
      setLoading(true);
      const response = await axiosInstance.get('/projects');
      setProjects(response.data);
      setError('');
    } catch (err) {
      setError('プロジェクトの取得に失敗しました');
      console.error('Error fetching projects:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProjects();
  }, []);

  const handleDelete = async (projectId: number) => {
    if (!window.confirm('このプロジェクトを削除してもよろしいですか？')) {
      return;
    }

    try {
      await axiosInstance.delete(`/projects/${projectId}`);
      fetchProjects();
    } catch (err) {
      setError('プロジェクトの削除に失敗しました');
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'active':
        return '進行中';
      case 'completed':
        return '完了';
      case 'on_hold':
        return '保留中';
      default:
        return status;
    }
  };

  const getStatusColor = (status: string): "success" | "warning" | "error" | "default" => {
    switch (status) {
      case 'active':
        return 'success';
      case 'completed':
        return 'default';
      case 'on_hold':
        return 'warning';
      default:
        return 'default';
    }
  };

  const filteredProjects = projects.filter(project =>
    project.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    project.description.toLowerCase().includes(searchQuery.toLowerCase())
  );

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="200px">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h5">
          プロジェクト一覧
        </Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={() => navigate('/projects/create')}
        >
          新規プロジェクト
        </Button>
      </Box>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <TextField
        fullWidth
        label="検索"
        variant="outlined"
        value={searchQuery}
        onChange={(e) => setSearchQuery(e.target.value)}
        sx={{ mb: 3 }}
      />

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))' }}>
        {filteredProjects.map((project) => (
          <Card key={project.id}>
            <CardContent>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1 }}>
                <Typography variant="h6" component="div">
                  {project.name}
                </Typography>
                <Chip
                  size="small"
                  label={getStatusLabel(project.status)}
                  color={getStatusColor(project.status)}
                />
              </Box>
              <Typography color="text.secondary" sx={{ mb: 1.5 }}>
                {project.description}
              </Typography>
              <Typography variant="subtitle2" sx={{ mb: 1 }}>
                タスク ({project.tasks.length})
              </Typography>
              <List dense>
                {project.tasks.slice(0, 3).map((task) => (
                  <ListItem key={task.id} disablePadding>
                    <ListItemText
                      primary={task.title}
                      sx={{ 
                        '& .MuiListItemText-primary': {
                          textOverflow: 'ellipsis',
                          overflow: 'hidden',
                          whiteSpace: 'nowrap',
                        }
                      }}
                    />
                  </ListItem>
                ))}
                {project.tasks.length > 3 && (
                  <ListItem disablePadding>
                    <ListItemText
                      primary={`他 ${project.tasks.length - 3} 件のタスク`}
                      sx={{ color: 'text.secondary' }}
                    />
                  </ListItem>
                )}
              </List>
            </CardContent>
            <CardActions sx={{ justifyContent: 'flex-end' }}>
              <IconButton
                size="small"
                onClick={() => {/* 編集処理 */}}
              >
                <EditIcon />
              </IconButton>
              <IconButton
                size="small"
                onClick={() => handleDelete(project.id)}
              >
                <DeleteIcon />
              </IconButton>
            </CardActions>
          </Card>
        ))}
      </Box>

      {filteredProjects.length === 0 && (
        <Paper sx={{ p: 2, textAlign: 'center' }}>
          <Typography color="text.secondary">
            プロジェクトが見つかりません
          </Typography>
        </Paper>
      )}
    </Box>
  );
};

export default ProjectList;
